# CRUD flow

How a resource is built in this app, end to end. Written from `Event`, which is
the reference implementation — read those files when a sketch here is not
enough.

| Layer         | File                                       |
| ------------- | ------------------------------------------ |
| Model         | `app/Models/Event.php`                     |
| Outbound Data | `app/Data/EventData.php`                   |
| Inbound Data  | `app/Data/EventFormData.php`               |
| Write action  | `app/Actions/UpsertEventAction.php`        |
| Controller    | `app/Http/Controllers/EventController.php` |
| Pages         | `resources/js/pages/events/`               |

Work through the steps in order — each assumes the one before it exists.

---

## 1. Migration and model

The model needs `#[Fillable]`. Nothing in this app calls `Model::unguard()`, so
without it `create()` and `update()` discard every attribute and the request
still redirects with no error.

```php
#[Fillable([
    'event_category_id', 'title', 'description', 'location_mode', 'venue',
    'date', 'start_date', 'end_date', 'registration_deadline',
    'capacity', 'waitlist', 'status',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasUlids;
}
```

List the columns the form submits; leave `id` and the timestamps out.

Fill in the factory's `definition()` while you are there. A generated stub
returns `[]` and fails on the first NOT NULL column, with an error that points
at the database rather than at the factory.

### Conditional queries go in a custom builder

Anything the model narrows by because of _who is asking_ — a permission, a
status alumni should not see — is query logic, not model state. Route it to a
custom Eloquent builder under `app/QueryBuilders/` and point the model at it
with `#[UseEloquentBuilder]`:

```php
class EventQueryBuilder extends Builder
{
    public function visibleTo(?User $user): static
    {
        if ($user?->can(PermissionEnum::EventsManage->value)) {
            return $this;
        }

        return $this->where('status', '!=', 'draft');
    }
}
```

```php
#[UseEloquentBuilder(EventQueryBuilder::class)]
class Event extends Model
```

The call site is unchanged — `Event::query()->visibleTo($user)` — and the model
stays limited to columns, relations, casts and the lacodix whitelists. A scope
on the model is the one-off that grows the model into a dumping ground.

Both spellings count. `#[Scope]` is the Laravel 12 attribute, while this repo
writes the older `scopeVisibleTo()` prefix, so the prefix is what to grep for.
Grepping only for the attribute returns nothing here and reads as a clean bill
of health.

`ConventionsTest` holds two lines of this rule, neither of them baselined:

- A model declares no scopes of its own. Any model that needs query logic has a
  builder to put it in, so there is nothing left for a scope to be.
- No model writes a query by hand. The `where` family is forbidden under
  `app/Models/` the same way it is in controllers, and for the same reason: a
  query spelled out in two places is found in only one of them.

Relations are not queries. `belongsTo()` and `hasMany()` declare structure and
use none of those calls, so they pass untouched. Scopes reaching a model through
the lacodix and spatie traits are not its own either, and are not counted,
because a third-party trait cannot be moved.

A method that answers a question about one loaded row stays on the model, but it
asks the builder rather than writing the query itself. `Event::attendingCount()`
is the shape: `EventRsvp::query()->attendingFor($this)->count()`.

## 2. Two Data objects, not one

The step most worth getting right, because getting it wrong fails quietly.

**`EventData`** — outbound. Describes a row that exists, so `id` is required. It
carries `#[TypeScript]`, which is what puts it in `generated.d.ts` for the pages
to type their props against.

**`EventFormData`** — inbound. Only what the editor submits, plus the validation
attributes. No `id`. It carries `#[TypeScript]` like every other Data object, so
the form page types its payload against the same shape the request validates.

```php
class EventFormData extends Data
{
    /** @var list<string> */
    public const array STATUSES = ['draft', 'published', 'cancelled', 'archived'];

    public function __construct(
        #[Exists(EventCategory::class, 'id')]
        public string $event_category_id,
        #[Max(255)]
        public string $title,
        #[WithCast(DateTimeInterfaceCast::class)]
        public CarbonImmutable $start_date,
        // An event cannot finish before it starts.
        #[WithCast(DateTimeInterfaceCast::class)]
        #[AfterOrEqual('start_date')]
        public CarbonImmutable $end_date,
        #[In(self::STATUSES)]
        public string $status,
    ) {}
}
```

**Type-hinting the outbound Data on `store()` is the classic bug.** A create form
has no `id` to send, so validation fails on `id`, Inertia bounces it back as a
302, and the message lands on a field with no input to display it — a save that
does nothing and says nothing:

```
STATUS: 302   SESSION ERRORS: {"id":["The id field is required."]}   COUNT: 0
```

Two smaller traps. PHP forbids spread in an attribute argument list, so write
`#[In(self::STATUSES)]`, not `#[In(...self::STATUSES)]`. And `config/data.php`
sets `date_format` to `DATE_ATOM`, so a bare `2026-10-17` or `2026-10-17T18:30`
is rejected — the payload must carry a full offset.

## 3. An Upsert action

`store()` and `update()` write the same columns, so the write lives in one place.

```php
class UpsertEventAction
{
    use AsAction;

    /**
     * Create an event, or update the one passed in.
     *
     * The event to update comes from the caller (route binding), never from the
     * payload: an id in the body would let anyone overwrite another event by
     * guessing its ULID.
     */
    public function handle(EventFormData $data, ?Event $event = null): Event
    {
        if ($event instanceof Event) {
            $event->update($data->toArray());

            return $event;
        }

        return Event::create($data->toArray());
    }
}
```

Do not reach for `updateOrCreate(['id' => $data->id ?? null], ...)`. It takes the
target row from the request body, which is an authorisation hole, and against a
non-nullable `$id` the `?? null` never fires anyway.

## 4. Controller

```php
public function create(): Response
{
    return Inertia::render('events/Create', [
        'categories' => EventCategoryData::collect(
            EventCategory::orderBy('name')->get()
        ),
    ]);
}

public function store(EventFormData $data, UpsertEventAction $action): RedirectResponse
{
    $event = $action->handle($data);

    // Inertia::flash, not ->with(): Response::resolveFlashData() pulls from
    // Inertia's own session key, which is what initializeFlashToast reads.
    Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

    return to_route('events.index');
}

public function edit(Event $event): Response
{
    return Inertia::render('events/Edit', [
        'event' => EventData::from($event),
        'categories' => EventCategoryData::collect(
            EventCategory::orderBy('name')->get()
        ),
    ]);
}

public function update(EventFormData $data, Event $event, UpsertEventAction $action): RedirectResponse
{
    $event = $action->handle($data, $event);

    Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

    return to_route('events.index');
}
```

`create()` and `edit()` must pass the same option lists, or the two pages drift.

Register with `Route::resource` inside the `['auth', 'verified']` group in
`routes/web.php`. That is what gives Wayfinder
`resources/js/routes/events/index.ts` with `index`, `create`, `store`, `edit`,
`update`, `destroy`.

Name the actions the resource does not have in `->except()`, and delete the
generated stub with them. `events` carries `->except('destroy')` because nothing
deletes an event: it is archived instead, which keeps the row and its audit
trail. A routed stub with an empty body is worse than no route, since the button
that eventually calls it answers 200 and changes nothing.

Every stub method the generator left behind needs a return type — PHPStan runs at
level 7, where `missingType.return` fails the build. `: void` is fine for a
method whose body is still a `//`.

### Authorization goes in a policy

`can:` middleware on a route knows the permission and nothing else. It never
sees the row, so any rule that depends on the record (who may open a draft, who
may undo an archive) cannot live there. `Event` therefore has an `EventPolicy`
and the events routes carry no `can:` at all.

Three places, and which one a rule belongs to:

| The rule depends on                | Where it goes                            |
| ---------------------------------- | ---------------------------------------- |
| a permission, with no model behind | `can:` middleware on the route           |
| a permission and the row           | a policy, via `Gate::authorize()`        |
| the row's state, not the user      | the controller, with its own status code |

`global-config` is the first kind: a staff-only page with no model, so a policy
for it would be an empty layer. Everything under `events` is the second.

```php
public function show(Event $event, Request $request): Response
{
    Gate::authorize('view', $event);
    ...
}
```

**Use `denyAsNotFound()` where a denial would leak.** A 403 on a draft event
tells the alumni who guessed the URL that it exists. The listing hides those
rows, so the detail page hides them the same way:

```php
public function view(User $user, Event $event): Response
{
    return Event::query()->visibleTo($user)->whereKey($event->getKey())->exists()
        ? Response::allow()
        : Response::denyAsNotFound();
}
```

**A state check is not authorization.** `RestoreEventController` asks the policy
who may restore, then answers 422 itself when the event is not archived: the
request was well-formed and the caller was allowed, there was simply nothing to
restore. Moving that into the policy would report it as 403 and send the next
person hunting through permissions. `redirectIfPast()` is out for the same
reason plus one more: a policy can only allow or deny, and that rule answers
with a toast and a redirect to the read-only page.

**Do not reach for `AuthorizesRequests`.** `$this->authorizeResource()` is
shorter wiring, but a trait's public methods count as methods of the class using
it, so `authorize`, `authorizeForUser` and `authorizeResource` would land on
`app/Http/Controllers/Controller.php`, which is itself one of the classes
convention 1 reflects over. The suite goes red. `Gate::authorize()` adds no
public method, and it states the rule inside the method the reader is already
in.

## 5. Pages: one Form, two wrappers

```
resources/js/pages/events/
├── Form.vue    every field, useForm, submit()
├── Create.vue  <EventForm :action="store.url()">
├── Edit.vue    <EventForm :action="update.url(id)" method="put" :event>
└── Index.vue
```

`Form.vue` owns the fields and the submission; the wrappers only decide where it
posts. Each wrapper stays at about twenty lines: `defineOptions({ layout })`, a
`<Head>`, and one `<EventForm>`.

```ts
const props = withDefaults(
    defineProps<{
        action: string;
        method?: 'post' | 'put';
        categories?: App.Data.EventCategoryData[];
        // Absent when creating, which is what leaves every field empty.
        event?: App.Data.EventData | null;
    }>(),
    { method: 'post', categories: () => [], event: null },
);

const form = useForm({
    title: props.event?.title ?? '',
    event_category_id: props.event?.event_category_id ?? '',
    // ...
});

function submit(status: 'draft' | 'published'): void {
    const submission = form.transform((data) => ({ ...data, status }));

    if (props.method === 'put') {
        submission.put(props.action);

        return;
    }

    submission.post(props.action);
}
```

Seed each field from `props.event?.x ?? <empty>`, using the column name the Data
object uses — `event_category_id`, not `category_id`. A mismatch is a `vue-tsc`
error rather than a runtime one, so run the typecheck.

The layout title and description go in `defineOptions`; the page still needs its
own `<Head>`.

## 6. Two frontend traps

**Stored datetimes are wall-clock, not instants.** The columns are plain
`dateTime`, so an offset in the payload is dropped on the way in and the value
reads back stamped `+00:00`. Passing that through `new Date()` shifts the hour by
the viewer's own offset — 18:30 renders as "1:30 am" in WIB. Convert by slicing
strings instead: `resources/js/lib/eventDateTime.ts` does exactly that, with the
offset pinned to `+00:00`. The same trap applies to any list or card that formats
a stored datetime.

**`transform()` breaks `form.errors` typing.** Renaming a key on the way out means
the server reports a name the form object never had, and `FormDataErrors` does
not know it. Read those back through a cast, and say why:

```ts
const errorFor = computed<Record<string, string | undefined>>(
    () => form.errors as Record<string, string | undefined>,
);
```

## 7. Test what fails silently

Write a feature test for the things that redirect happily while doing nothing:

- a row is actually created — `assertSessionHasNoErrors()` turns a quiet 302 into
  a readable failure
- an update leaves the row count alone
- an `id` smuggled into the payload does not overwrite another row
- an invalid payload is still rejected
- each policy ability denies the role that should not have it, and the hidden
  row answers 404 rather than 403

---

# Conventions

Seven rules every resource follows. Five of them are checked by
`tests/Unit/ConventionsTest.php`, which runs inside `composer ci:check`, so
breaking one fails the pull request rather than the review.

| #   | Rule                                              | What enforces it                                    |
| --- | ------------------------------------------------- | --------------------------------------------------- |
| 1   | A controller exposes REST actions only            | `ConventionsTest`, reflection over every controller |
| 2   | Search and filter go through the lacodix traits   | `ConventionsTest`, no `where` family in controllers |
| 3   | The frontend reaches the API through a service    | `ConventionsTest`, `axios` only under `services/`   |
| 4   | Every URL comes from wayfinder                    | `ConventionsTest`, no absolute path literals        |
| 5   | Payloads are `app/Data` objects                   | `ConventionsTest` + `composer types:sync`           |
| 6   | A `.vue` file over 300 lines is split             | `ConventionsTest`, line count per file              |
| 7   | Laravel standards, efficient, no over-engineering | Pint, PHPStan, lazy-loading guard, and review       |

## 1. A controller exposes REST actions only

`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. Nothing else,
public. A private helper that shapes a query is a scope or a filter that has not
been moved yet, and it is invisible to everything but that one controller.

## 2. Search and filter go through the lacodix traits

`Event` carries `IsSearchable`, `IsSortable` and `HasFilters`, so one whitelist
per model decides what a query parameter may reach:

```php
protected array $searchable = ['title', 'venue', 'category.name'];
protected array $sortable = ['start_date' => 'desc', 'title' => null];

public function filters(): array
{
    return [StringFilter::make('status')->setQueryName('status')->setMode(FilterMode::EQUAL)];
}
```

The controller then reads `->filterByQueryString()->searchByQueryString()->sortByQueryString()`
and nothing else. Hand-written `where` clauses over request input are how a sort
column becomes an injection point and a filter silently stops being applied, so
the check forbids the `where` family in controllers outright. A fixed ordering
(`orderBy`, `latest`) is not a filter and stays allowed.

Authorization is not a filter either: the draft gate is `visibleTo()` on
`EventQueryBuilder`, so every listing and `EventPolicy::view()` run the same
rule.

## 3. The frontend reaches the API through a service

One file per resource under `resources/js/services/`, exporting typed functions:
`resources/js/services/eventService.ts` is the reference. `axios` appears there
and nowhere else, so the URL, the parameter names and the response type live in
one place instead of being restated at each call site.

## 4. Every URL comes from wayfinder

Import the generated helper (`import { index } from '@/routes/api/v1/events'`)
and call `index.url()`. A quoted absolute path is a route spelled out by hand: it
survives a rename that the generated helpers would have caught at build time, and
it is exactly how a table ends up fetching the Inertia page instead of the API.

## 5. Payloads are `app/Data` objects

Every class in `app/Data` carries `#[TypeScript]`, so `generated.d.ts` describes
what the backend actually sends and the pages type their props against it.
`composer types:sync` regenerates the file and fails if the result differs from
what is committed, so a changed Data object cannot reach main with stale types.

## 6. A `.vue` file over 300 lines is split

Past that a page is doing more than one job. `events/Index.vue` owns the filter
state and the query; the filter card, the tab switch, the card grid and the table
are each their own component under `components/alumni/`. Two files predate the
rule and are baselined in the test: `pages/Welcome.vue` and `pages/alumni/Home.vue`.

## 7. Laravel standards, efficient, no over-engineering

Pint and PHPStan level 7 cover the style and the types.
`Model::preventLazyLoading()` is on outside production, so an N+1 fails the suite
instead of slowing a page. The last part is a judgement: prefer the framework's
own mechanism over a new abstraction, and do not add a layer before there are two
callers for it. No test can check that, so it belongs to the reviewer.

---

# Before you push

Run `composer ci:check`. It is the same command GitHub Actions runs
(`.github/workflows/tests.yml`), and it chains lint, types, and tests:

```
composer ci:check
```

which expands to:

| Command                    | Catches                                  |
| -------------------------- | ---------------------------------------- |
| `bun run check`            | JS/Vue lint and formatting               |
| `bun run types:check`      | `vue-tsc --noEmit`                       |
| `composer lint:check`      | `pint --parallel --test`                 |
| `composer types:check`     | `phpstan analyse` (level 7)              |
| `php artisan test`         | the Pest suite, floored at 90% coverage  |
| `bin/no-zero-coverage.php` | a file under `app/` that no test reaches |

Two generator commands run **before** that, and only when their input changed:

```bash
php artisan typescript:transform          # after adding or editing a #[TypeScript] Data object
php artisan wayfinder:generate --with-form  # after changing routes, if the dev server is not running
```

`typescript:transform` writes `resources/js/generated/generated.d.ts`, which
**is committed** — so a stale file is a real diff and a real type error for the
next person.

`wayfinder:generate` writes `resources/js/routes/`, `resources/js/actions/`, and
`resources/js/wayfinder/`, all of which are gitignored and regenerated by the
Vite plugin on `bun run dev`; run it by hand only when you need those files
without a dev server (a fresh clone, or CI). **`--with-form` is not optional.**
`vite.config.ts` configures the plugin with `formVariants: true`, so the pages
call `store.form()` and friends; regenerating without the flag drops those and
breaks `vue-tsc` across a dozen auth and settings pages.

If lint reports fixable problems, `bun run check:fix` and `composer lint` apply
them. Re-run `composer ci:check` afterwards. Note that `bun run check` covers
Markdown as well as JS and Vue, so a new `.md` file can fail the gate on
formatting alone.

## Coverage

`composer ci:check` runs the suite with `--coverage --min=90`, so a coverage
driver is not optional. Install it once, matching your PHP version:

```bash
sudo apt install php8.5-pcov
```

Without it the suite stops at `Code coverage driver not available`. CI installs
it through `coverage: pcov` on `setup-php`, so the gate is the same in both
places.

Two scripts read the report without the floor, for when you are hunting a gap
rather than gating a push:

```bash
composer test:coverage        # per-file table in the terminal
composer test:coverage:html   # browsable report in storage/coverage (gitignored)
```

`phpunit.xml` already limits `<source>` to `app`, and nothing is excluded from
it, so the percentage is the real one rather than a number massaged by
exclusions.

### Why a percentage is not enough

The floor is 90 against a baseline of 95.3. In a codebase this small that gap is
worth about 46 untested lines, and the global-config feature, the one that
reached `main` with no tests at all, was 37 executable lines across eight files.
It would have passed. A percentage over 770 lines cannot see a whole feature
arrive untested.

So `composer test` runs a second check over the same clover report:

```
no-zero-coverage: every file under app/ is reached by a test (1 allowed at 0%).
```

`bin/no-zero-coverage.php` fails the build when any file under `app/` has tests
covering none of it, which is the shape an untested feature actually has. Its
`ALLOWED` list holds the files where running the code in a test would prove less
than what already runs it; each entry carries that reason, and the script says
so when an entry stops being true.

Raise `--min` when the baseline moves up for a real reason, never to chase the
number. On a small codebase a tight floor buys ceremony tests, not safety.

What the percentage will not tell you: whether each branch of an authorization
rule is exercised. `EventPolicy` reaches 100% the moment one test touches each
method, while the denial path stays unproven. That matrix is checked by hand,
against `EventAccessControlTest` and `RestoreEventTest`.

It also weighs every line the same. Covering `PermissionEnum`, which is 300
lines of `match` arms transcribed from the ACL workbook, moved the total by 16
points in one commit without making a single screen safer. Read the per-file
column, not the total.

## Known baseline

None. The suite is green, so a failure in your run is yours.
