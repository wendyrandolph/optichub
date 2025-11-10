@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
    @php
        // Use the tenant ID passed from controller; fall back to the authenticated user's tenant_id
$tenantParam = $tenant ?? (auth()->user()->tenant_id ?? null);

// Small helper to map status → Tailwind colors
function invoice_status_classes($status)
{
    $s = strtolower((string) $status);
    return match ($s) {
        'paid' => 'bg-green-500 text-white',
        'overdue' => 'bg-red-500 text-white',
        'draft' => 'bg-gray-500 text-white',
        'sent' => 'bg-blue-500 text-white',
        default => 'bg-black text-white',
            };
        }
    @endphp

    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">Invoices</h1>

            {{-- Create invoice (tenant-aware) --}}
            @if ($tenantParam)
                <a href="{{ route('tenant.invoices.create', ['tenant' => $tenantParam]) }}" class="btn btn-add">
                    <i class="fa-solid fa-plus mr-1"></i> Create New Invoice
                </a>
            @endif
        </div>

        <div class="table-wrapper">
            <table class="table-opportunities w-full">
                <thead>
                    <tr>
                        <th class="text-left">Invoice #</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Issue Date</th>
                        <th class="text-left">Due Date</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        @php
                            // Support array or Eloquent model shape
                            $id = $invoice['id'] ?? ($invoice->id ?? null);
                            $number = $invoice['invoice_number'] ?? ($invoice->invoice_number ?? '—');
                            $clientName = $invoice['client_name'] ?? (optional($invoice->client)->full_name ?? '—');
                            $issueDate =
                                $invoice['issue_date'] ??
                                (isset($invoice->issue_date) ? $invoice->issue_date->toDateString() : '—');
                            $dueDate =
                                $invoice['due_date'] ??
                                (isset($invoice->due_date) ? $invoice->due_date->toDateString() : '—');
                            $status = $invoice['status'] ?? ($invoice->status ?? '—');
                            $statusClasses = invoice_status_classes($status);
                        @endphp

                        <tr>
                            <td>{{ $number }}</td>
                            <td>{{ $clientName }}</td>
                            <td>{{ $issueDate }}</td>
                            <td>{{ $dueDate }}</td>
                            <td>
                                <span class="inline-block px-2 py-1 rounded-md text-xs font-medium {{ $statusClasses }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="space-x-2">
                                @if ($tenantParam && $id)
                                    <a href="{{ route('tenant.invoices.show', ['tenant' => $tenantParam, 'invoice' => $id]) }}"
                                        class="tooltip" title="View Invoice">
                                        <i class="fa-solid fa-eye"></i>
                                        <span class="tooltiptext">View</span>
                                    </a>

                                    {{-- Delete (uses method spoofing + CSRF) --}}
                                    <form
                                        action="{{ route('tenant.invoices.destroy', ['tenant' => $tenantParam, 'invoice' => $id]) }}"
                                        method="POST" style="display:inline;"
                                        onsubmit="return confirm('Delete this invoice?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tooltip" title="Delete Invoice">
                                            <i class="fa-solid fa-trash"></i>
                                            <span class="tooltiptext">Delete</span>
                                        </button>
                                    </form>

                                    {{-- Optional: PDF / Send actions if routes exist --}}
                                    @if (Route::has('tenant.invoices.pdf'))
                                        <a href="{{ route('tenant.invoices.pdf', ['tenant' => $tenantParam, 'invoice' => $id]) }}"
                                            class="tooltip" title="Download PDF">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span class="tooltiptext">PDF</span>
                                        </a>
                                    @endif

                                    @if (Route::has('tenant.invoices.send'))
                                        <form
                                            action="{{ route('tenant.invoices.send', ['tenant' => $tenantParam, 'invoice' => $id]) }}"
                                            method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tooltip" title="Send to Client">
                                                <i class="fa-solid fa-paper-plane"></i>
                                                <span class="tooltiptext">Send</span>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6">
                                No invoices yet. Click “Create New Invoice” to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination (if $invoices is a LengthAwarePaginator) --}}
            @if (method_exists($invoices, 'links'))
                <div class="mt-4">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
