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
credit_detail: App.Data.CreditCardDetailData | null,
};
export type AccountFormData = {
name: string,
type: App.Enums.AccountType,
initial_balance: string,
credit_limit: string | null,
billing_cycle_start_day: number | null,
billing_cycle_end_day: number | null,
payment_due_offset_days: number | null,
default_interest_rate_monthly: string | null,
default_admin_fee_percentage: string | null,
};
export type AccountHistoryData = {
key: string,
kind: string,
ref_id: number,
date: string,
title: string,
subtitle: string,
amount: string,
direction: string,
creator_name: string,
can_edit: boolean,
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
export type CreditCardDetailData = {
billing_cycle_start_day: number,
billing_cycle_end_day: number,
payment_due_offset_days: number,
default_interest_rate_monthly: string,
default_admin_fee_percentage: string,
credit_limit: string | null,
};
export type InvitationFormData = {
email: string,
role: App.Enums.TenantRole,
};
export type MemberRoleFormData = {
role: App.Enums.TenantRole,
};
export type PlanData = {
id: number,
name: string,
slug: string,
price: string,
billing_period: string,
is_active: boolean,
};
export type SubscriptionData = {
id: number,
plan: App.Data.PlanData,
status: App.Enums.SubscriptionStatus,
current_period_end: string | null,
};
export type TenantCreateFormData = {
name: string,
};
export type TenantData = {
id: number,
name: string,
subdomain: string | null,
created_at: string,
member_count: number,
memberships: {
id: number,
name: string,
subdomain: string | null,
url: string,
role: string | null,
is_current: boolean,
}[],
};
export type TenantInvitationData = {
id: number,
email: string,
role: App.Enums.TenantRole,
status: App.Enums.InvitationStatus,
expires_at: string,
accept_url: string,
can_resend: boolean,
can_revoke: boolean,
};
export type TenantMemberData = {
id: number,
name: string,
email: string,
role: string,
joined_at: string | null,
is_owner: boolean,
is_current_user: boolean,
can_change_role: boolean,
can_remove: boolean,
};
export type TenantSettingsFormData = {
name: string,
subdomain: string | null,
};
export type TransactionData = {
id: number,
account_id: number,
account_name: string,
category_id: number | null,
category_name: string | null,
category_icon: string | null,
type: App.Enums.TransactionType,
amount: string,
description: string | null,
transaction_date: string,
creator_name: string,
receipt_url: string,
linked_account_id: number | null,
linked_account_name: string | null,
statement_period_end: string | null,
due_date: string | null,
can_edit: boolean,
can_delete: boolean,
};
export type TransactionFormData = {
account_id: number,
category_id: number | null,
linked_account_id: number | null,
type: App.Enums.TransactionType,
amount: string,
description: string | null,
transaction_date: string,
};
export type TransferData = {
id: number,
from_account_id: number,
from_account_name: string,
to_account_id: number,
to_account_name: string,
amount: string,
description: string | null,
transfer_date: string,
creator_name: string,
can_edit: boolean,
can_delete: boolean,
};
export type TransferFormData = {
from_account_id: number,
to_account_id: number,
amount: string,
description: string | null,
transfer_date: string,
};
}
namespace Enums {
export type AccountType = 'cash' | 'bank' | 'ewallet' | 'credit_card' | 'paylater' | 'other';
export type BillingPeriod = 'monthly' | 'yearly';
export type CategoryType = 'income' | 'expense';
export type InvitationStatus = 'pending' | 'accepted' | 'expired' | 'revoked';
export type PermissionEnum = 'tenant.view' | 'tenant.settings' | 'tenant.delete' | 'accounts.create' | 'accounts.manage' | 'categories.manage' | 'transactions.create' | 'transactions.manage' | 'transactions.manage-own' | 'transfers.create' | 'transfers.manage' | 'transfers.manage-own' | 'members.invite' | 'members.remove' | 'members.manage-role' | 'billing.view' | 'billing.manage';
export type SubscriptionStatus = 'active' | 'past_due' | 'cancelled' | 'expired';
export type TenantRole = 'owner' | 'admin' | 'member';
export type TransactionType = 'income' | 'expense' | 'bill_payment';
}
}
