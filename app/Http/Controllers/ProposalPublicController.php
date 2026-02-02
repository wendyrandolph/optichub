<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProposalPublicController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $proposal = Proposal::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('public_token', $token)
            ->orWhere('unique_share_token', $token)
            ->first();

        if (!$proposal) {
            abort(404);
        }

        $isDraft = strtolower((string) $proposal->status) === 'draft';
        if ($isDraft) {
            abort(404);
        }

        $proposal->load(['items', 'tenant', 'client', 'lead', 'contract']);
        $paymentSchedule = $proposal->paymentScheduleItems()->get();
        $signature = $proposal->signatures()->latest()->first();

        return view('proposals.show_client', compact('proposal', 'paymentSchedule', 'signature'));
    }

    public function sign(Request $request, string $token)
    {
        $proposal = Proposal::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('public_token', $token)
            ->orWhere('unique_share_token', $token)
            ->first();

        if (!$proposal) {
            abort(404);
        }

        if (strtolower((string) $proposal->status) !== 'sent') {
            return redirect()->back()->with('success_message', 'This proposal has already been signed.');
        }

        $validated = $request->validate([
            'signer_name' => ['required', 'string', 'max:255'],
            'signer_email' => ['required', 'email', 'max:255'],
            'signature_text' => ['required', 'string', 'max:255'],
            'agreed' => ['accepted'],
        ]);

        $proposal->load(['items', 'tenant', 'client', 'lead', 'contract']);

        $proposalPdf = Pdf::loadView('proposals.pdf', [
            'proposal' => $proposal,
            'paymentSchedule' => $proposal->paymentScheduleItems()->get(),
            'tenant' => $proposal->tenant,
        ])->setPaper('letter');

        $proposalPdfBinary = $proposalPdf->output();
        $proposalHash = hash('sha256', $proposalPdfBinary);

        $signature = ProposalSignature::create([
            'proposal_id' => $proposal->id,
            'signed_at' => now(),
            'signer_name' => $validated['signer_name'],
            'signer_email' => $validated['signer_email'],
            'signer_ip' => $request->ip(),
            'signer_user_agent' => $request->userAgent(),
            'signature_text' => $validated['signature_text'],
            'snapshot_hash' => $proposalHash,
            'agreed' => true,
        ]);

        $proposal->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $proposalPath = 'proposals/signed/' . $proposal->id . '-' . Str::random(8) . '.pdf';
        Storage::disk('public')->put($proposalPath, $proposalPdfBinary);
        $proposal->update(['approved_pdf_path' => $proposalPath]);

        if ($proposal->contract) {
            $contract = $proposal->contract;
            $contract->update([
                'status' => 'signed',
                'signed_at' => now(),
                'signer_name' => $validated['signer_name'],
                'signer_email' => $validated['signer_email'],
                'signer_ip' => $request->ip(),
                'signer_user_agent' => $request->userAgent(),
            ]);

            $contractPdf = Pdf::loadView('proposals.contract-pdf', [
                'tenant' => $proposal->tenant,
                'proposal' => $proposal,
                'contract' => $contract,
                'template' => null,
            ])->setPaper('letter');
            $contractPath = 'contracts/signed/' . $contract->id . '-' . Str::random(8) . '.pdf';
            Storage::disk('public')->put($contractPath, $contractPdf->output());
            $contract->update(['pdf_path' => $contractPath]);
        }

        return redirect()->back()->with('success_message', 'Proposal approved.');
    }
}
