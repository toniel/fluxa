declare namespace App {
namespace Data {
export type AccountData = {
id: number,
name: string,
type: App.Enums.AccountType,
balance: string,
initial_balance: string,
is_archived: boolean,
logo_url: string,
};
export type AccountFormData = {
name: string,
type: App.Enums.AccountType,
initial_balance: string,
};
export type CategoryData = {
id: number,
name: string,
type: App.Enums.CategoryType,
icon: string | null,
is_default: boolean,
};
export type CategoryFormData = {
name: string,
type: App.Enums.CategoryType,
icon: string | null,
is_default: boolean,
};
}
namespace Enums {
export type AccountType = 'cash' | 'bank' | 'ewallet' | 'other';
export type CategoryType = 'income' | 'expense';
export type PermissionEnum = 'tenant.view' | 'tenant.settings' | 'tenant.delete' | 'accounts.create' | 'accounts.manage' | 'categories.manage' | 'transactions.create' | 'transactions.manage' | 'transactions.manage-own' | 'transfers.create' | 'transfers.manage' | 'transfers.manage-own' | 'members.invite' | 'members.remove' | 'members.manage-role' | 'billing.view' | 'billing.manage';
export type TenantRole = 'owner' | 'admin' | 'member';
}
}
