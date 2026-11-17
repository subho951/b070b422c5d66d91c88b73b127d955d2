<?php

return [
    'customers' => ['label' => 'Customers', 'table' => 'customers', 'fields' => [
        'name' => 'required|string|max:180', 'type' => 'required|in:company,individual', 'contact_person' => 'nullable|string|max:120',
        'email' => 'required|email|max:255', 'phone' => 'nullable|string|max:40', 'country' => 'required|string|max:100',
        'billing_address' => 'nullable|string|max:5000', 'tax_identifier' => 'nullable|string|max:100', 'billing_currency' => 'required|exists:currencies,code',
        'status' => 'required|in:active,inactive', 'renewal_date' => 'nullable|date', 'renewal_amount' => 'nullable|numeric|min:0|max:999999999',
        'domain_provider' => 'nullable|string|max:255', 'server_provider' => 'nullable|string|max:255', 'internal_notes' => 'nullable|string|max:5000',
    ]],
    'projects' => ['label' => 'Customer projects', 'table' => 'projects', 'fields' => [
        'title' => 'required|string|max:255', 'reference' => 'required|string|max:60', 'customer_id' => 'required|exists:customers,id,deleted_at,NULL',
        'description' => 'nullable|string|max:5000', 'status' => 'required|in:planned,in_progress,completed,on_hold,cancelled',
        'priority' => 'required|in:low,normal,high', 'start_date' => 'nullable|date', 'target_completion_date' => 'nullable|date|after_or_equal:start_date',
        'agreed_budget' => 'nullable|numeric|min:0|max:999999999', 'currency' => 'required|exists:currencies,code', 'internal_notes' => 'nullable|string|max:5000',
    ]],
    'expenses' => ['label' => 'Expenses', 'table' => 'expenses', 'fields' => [
        'title' => 'required|string|max:255', 'expense_category_id' => 'required|exists:expense_categories,id', 'amount' => 'required|numeric|gt:0|max:999999999',
        'currency' => 'required|exists:currencies,code', 'expense_date' => 'required|date', 'vendor' => 'nullable|string|max:180',
        'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:5000',
    ]],
    'expense-categories' => ['label' => 'Expense categories', 'table' => 'expense_categories', 'fields' => ['name' => 'required|string|max:100', 'description' => 'nullable|string|max:5000', 'is_active' => 'required|boolean']],
    'companies' => ['label' => 'Company master', 'fields' => ['name' => 'required|string|max:180', 'email' => 'required|email|max:255', 'phone' => 'nullable|string|max:40', 'address' => 'required|string|max:5000', 'tax_identifier' => 'nullable|string|max:100']],
    'banks' => ['label' => 'Bank master', 'fields' => ['name' => 'required|string|max:180', 'account_holder' => 'required|string|max:180', 'account_number' => 'required|string|max:100', 'branch' => 'nullable|string|max:180', 'ifsc_swift' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:5000']],
    'workflow' => ['label' => 'Work flow', 'fields' => ['name' => 'required|string|max:180', 'description' => 'required|string|max:5000', 'sort_order' => 'required|integer|min:0|max:9999']],
    'project-categories' => ['label' => 'Project categories', 'fields' => ['name' => 'required|string|max:100', 'description' => 'nullable|string|max:5000']],
    'team' => ['label' => 'Team', 'fields' => ['name' => 'required|string|max:180', 'designation' => 'required|string|max:180', 'featured_image_id' => 'nullable|integer|exists:media_assets,id', 'email' => 'nullable|email|max:255', 'description' => 'nullable|string|max:5000']],
    'clients' => ['label' => 'Clients', 'fields' => ['name' => 'required|string|max:180', 'website' => 'nullable|url|max:500', 'featured_image_id' => 'nullable|integer|exists:media_assets,id', 'description' => 'nullable|string|max:5000']],
    'packages' => ['label' => 'Packages & pricing', 'fields' => ['name' => 'required|string|max:180', 'amount' => 'required|numeric|min:0|max:999999999', 'currency' => 'required|exists:currencies,code', 'description' => 'required|string|max:5000']],
    'products' => ['label' => 'Product catalog', 'fields' => ['name' => 'required|string|max:180', 'description' => 'nullable|string|max:5000']],
    'gst-states' => ['label' => 'GST states', 'fields' => ['name' => 'required|string|max:180']],
    'users' => ['label' => 'User master', 'table' => 'users', 'fields' => ['name' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => 'nullable|string|min:12|max:255', 'role' => 'required|in:administrator,staff']],
];
