<?php
// app/Http/Controllers/EmailController.php
namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\EmailSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoggedEmail;
use App\Models\Email;

class EmailController extends Controller
{
  public function __construct(private EmailSendService $mailer) {}

  public function index(Tenant $tenant)
  {
    $emails = \App\Models\Email::where('tenant_id', $tenant->id)
      ->latest()
      ->paginate(12);

    return view('emails.index', compact('tenant', 'emails'));
  }

  public function create(\App\Models\Tenant $tenant)
  {
    $clients = \App\Models\Client::where('tenant_id', $tenant->id)
      ->orderBy('lastName')->get(['id', 'lastName as name', 'email']);

    $leads = \App\Models\Lead::where('tenant_id', $tenant->id)
      ->orderBy('name')->get(['id', 'name', 'email']);

    return view('emails.create', compact('tenant', 'clients', 'leads'));
  }

  public function store(Request $request, \App\Models\Tenant $tenant)
  {
    $data = $request->validate([
      'related_type'    => 'required|in:client,lead',
      'related_id'      => 'required|integer',
      'recipient_email' => 'required|email',
      'subject'         => 'required|string|max:255',
      'body'            => 'nullable|string',
    ]);

    // Send
    Mail::to($data['recipient_email'])
      // ->withTenantAutoBcc($tenant) // if you added that macro
      ->send(new LoggedEmail($data['subject'], $data['body'] ?? ''));

    // Log
    Email::create([
      'tenant_id'      => $tenant->id,
      'subject'        => $data['subject'],
      'recipient_email' => $data['recipient_email'],
      'related_type'   => $data['related_type'],
      'related_id'     => $data['related_id'],
      'body'           => $data['body'] ?? null,
      'date_sent'      => now(),
    ]);

    return redirect()
      ->route('tenant.emails.index', ['tenant' => $tenant->id])
      ->with('success', 'Email sent and logged.');
  }
}
