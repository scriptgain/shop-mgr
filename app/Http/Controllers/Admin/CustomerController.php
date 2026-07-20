<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.customers.index', [
            'customers' => Customer::search($request->string('q')->toString() ?: null)
                ->when($request->input('filter') === 'repeat', fn ($q) => $q->where('orders_count', '>', 1))
                ->when($request->input('filter') === 'marketing', fn ($q) => $q->where('accepts_marketing', true))
                ->when($request->input('filter') === 'guests', fn ($q) => $q->whereNull('password'))
                ->orderByDesc('total_spent_cents')
                ->paginate((int) config('shop.rows_per_page', 25))
                ->withQueryString(),
            'filters' => $request->only(['q', 'filter']),
            'tabCounts' => [
                'all' => Customer::count(),
                'repeat' => Customer::where('orders_count', '>', 1)->count(),
                'marketing' => Customer::where('accepts_marketing', true)->count(),
                'guests' => Customer::whereNull('password')->count(),
            ],
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders.items', 'addresses']);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $customer->orders()->paginate(10),
        ]);
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer->update($data + ['accepts_marketing' => $request->boolean('accepts_marketing')]);

        return back()->with('status', 'Customer saved.');
    }

    public function destroy(Customer $customer)
    {
        // Soft delete: their orders keep pointing at the record.
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = collect($request->input('ids', []))->filter()->all();
        $count = Customer::whereIn('id', $ids)->get()->each->delete()->count();

        return back()->with('status', "Deleted {$count} customer(s).");
    }
}
