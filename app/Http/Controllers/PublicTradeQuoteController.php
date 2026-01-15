<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\TradeJob;
use App\Models\TradeQuote;
use App\Models\TradeQuoteAcceptance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PublicTradeQuoteController extends Controller
{
    public function show(string $token)
    {
        $quote = $this->findByToken($token);
        if (!$quote) {
            abort(404);
        }

        $quote->load(['tenant', 'client', 'company', 'items', 'acceptance']);
        if (Schema::hasColumn('trade_quotes', 'last_viewed_at')) {
            $quote->forceFill(['last_viewed_at' => now()])->save();
        }

        $expired = $quote->expires_at && now()->greaterThan($quote->expires_at);
        $archived = $quote->status === 'archived';
        $tenant = $quote->tenant;
        $logoPath = $tenant?->logo_url ?? $tenant?->logo_path ?? null;
        $logoUrl = null;
        if ($logoPath) {
            $logoUrl = str_starts_with($logoPath, 'http')
                ? $logoPath
                : (Storage::exists($logoPath) ? Storage::url($logoPath) : asset(ltrim($logoPath, '/')));
        }
        $brand = [
            'name' => $tenant?->brand_name ?? $tenant?->name ?? config('app.name', 'Renlo'),
            'logo' => $logoUrl ?? asset('images/renlo.svg'),
            'primary' => $tenant?->brand_primary ?? $tenant?->primary_color ?? '#0B1F52',
            'secondary' => $tenant?->brand_secondary ?? $tenant?->secondary_color ?? '#111827',
            'support_email' => $tenant?->support_email ?? config('mail.from.address'),
            'support_phone' => $tenant?->support_phone ?? $tenant?->phone,
            'location' => $tenant?->location,
        ];

        return view('public.trade-quote', [
            'quote' => $quote,
            'archived' => $archived,
            'expired' => $expired,
            'tenant' => $tenant,
            'brand' => $brand,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $quote = $this->findByToken($token);
        if (!$quote) {
            abort(404);
        }

        $quote->load(['items', 'acceptance']);

        if ($quote->status === 'archived') {
            return redirect()
                ->route('public.trade-quotes.show', ['token' => $token])
                ->with('error_message', 'This quote has been archived. Please contact your provider.');
        }

        if ($quote->status === 'accepted' || $quote->acceptance) {
            return redirect()
                ->route('public.trade-quotes.show', ['token' => $token])
                ->with('error_message', 'This quote has already been accepted.');
        }

        if ($quote->expires_at && now()->greaterThan($quote->expires_at)) {
            return redirect()
                ->route('public.trade-quotes.show', ['token' => $token])
                ->with('error_message', 'This quote has expired.');
        }

        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:255'],
            'signer_email' => ['nullable', 'email', 'max:255'],
            'signature' => ['required', 'string', 'max:255'],
        ]);

        TradeQuoteAcceptance::create([
            'trade_quote_id' => $quote->id,
            'signer_name' => $data['signer_name'],
            'signer_email' => $data['signer_email'] ?? null,
            'signature' => $data['signature'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accepted_at' => now(),
        ]);

        $quote->status = 'accepted';
        $quote->save();

        if (!$quote->trade_job_id) {
            $job = TradeJob::create([
                'tenant_id' => $quote->tenant_id,
                'client_id' => $quote->client_id,
                'company_id' => $quote->company_id,
                'type' => 'service',
                'status' => 'open',
                'summary' => $quote->title,
                'description' => $quote->notes,
            ]);
            $quote->trade_job_id = $job->id;
            $quote->save();
        }

        ActivityLog::record(
            $quote->tenant_id,
            null,
            $quote,
            'quote.accepted',
            'Trade quote accepted',
            [
                'signer_name' => $data['signer_name'],
                'signer_email' => $data['signer_email'] ?? null,
            ]
        );

        return redirect()
            ->route('public.trade-quotes.show', ['token' => $token])
            ->with('success_message', 'Thank you. The quote is accepted.');
    }

    protected function findByToken(string $token): ?TradeQuote
    {
        $hash = hash('sha256', $token);

        return TradeQuote::query()
            ->where('token_hash', $hash)
            ->first();
    }
}
