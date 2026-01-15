<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ClientCompany;
use App\Models\Contact;
use App\Models\ServiceLocation;
use App\Models\TradeJob;
use App\Models\TradeJobItem;
use App\Models\TradeAppointment;
use App\Models\AppointmentAssignment;
use App\Models\TradeJobTimer;
use App\Models\TradePtoType;
use App\Models\TradePtoBalance;
use App\Models\TradePtoRequest;
use App\Models\TradePtoLedger;
use App\Models\Lead;
use App\Models\TradeLeadEvent;
use App\Models\TechShift;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\TradeQuote;
use App\Models\TradeQuoteItem;
use App\Services\Trades\TradePtoService;

class TradesReportsSeeder extends Seeder
{
    public function run(): void
    {
        if (!class_exists(TradesTenantSeeder::class)) {
            return;
        }

        $this->call(TradesTenantSeeder::class);

        $tenant = Tenant::where('workspace_type', 'trades')->first() ?? Tenant::find(2);
        if (!$tenant) {
            return;
        }

        if (Schema::hasColumn('tenants', 'overtime_enabled')) {
            $tenant->update([
                'overtime_enabled' => true,
                'overtime_daily_hours' => 8,
                'overtime_weekly_hours' => 40,
            ]);
        }

        $admin = User::where('tenant_id', $tenant->id)->where('role', 'admin')->first();
        if (!$admin) {
            return;
        }

        $company = ClientCompany::firstOrCreate(
            ['tenant_id' => $tenant->id, 'company_name' => 'Summit Property Group'],
            ['client_status' => 'active']
        );

        $contacts = [];
        $clientSeeds = [
            ['firstName' => 'Jordan', 'lastName' => 'Lee', 'email' => 'client@trades.test', 'phone' => '555-555-0100'],
            ['firstName' => 'Marissa', 'lastName' => 'Cole', 'email' => 'marissa@trades.test', 'phone' => '555-555-0122'],
            ['firstName' => 'Evan', 'lastName' => 'Tran', 'email' => 'evan@trades.test', 'phone' => '555-555-0188'],
        ];

        foreach ($clientSeeds as $seed) {
            $contacts[] = Contact::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $seed['email']],
                [
                    'client_company_id' => $company->id,
                    'firstName' => $seed['firstName'],
                    'lastName' => $seed['lastName'],
                    'status' => 'active',
                    'phone' => $seed['phone'],
                ]
            );
        }

        $serviceLocations = [];
        foreach ($contacts as $index => $contact) {
            $serviceLocations[] = ServiceLocation::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'client_id' => $contact->id,
                    'label' => 'Primary Location ' . ($index + 1),
                ],
                [
                    'client_company_id' => $company->id,
                    'address_line1' => (100 + $index) . ' Broad Street',
                    'city' => 'Denver',
                    'state' => 'CO',
                    'postal_code' => '8020' . ($index + 1),
                    'country' => 'US',
                    'access_notes' => 'Gate code: 1234',
                ]
            );
        }

        $techs = [];
        for ($i = 1; $i <= 5; $i++) {
            $email = "tech{$i}@trades.test";
            $username = "trades-tech-{$i}";
            $techData = [
                'tenant_id' => $tenant->id,
                'username' => $username,
                'first_name' => 'Tech',
                'last_name' => 'User ' . $i,
                'password' => bcrypt('password123'),
                'role' => 'tech',
                'is_beta' => false,
                'must_change_password' => false,
            ];

            if (Schema::hasColumn('users', 'hired_at')) {
                $techData['hired_at'] = now()->subMonths(12 - $i)->startOfDay();
            }

            $techs[] = User::updateOrCreate(
                ['email' => $email],
                $techData
            );
        }

        $ptoTypes = [
            ['name' => 'Vacation', 'code' => 'VAC'],
            ['name' => 'Sick', 'code' => 'SICK'],
        ];

        foreach ($ptoTypes as $type) {
            TradePtoType::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $type['code']],
                [
                    'name' => $type['name'],
                    'accrual_rate_hours' => 4,
                    'accrual_period' => 'month',
                    'carryover_enabled' => true,
                    'carryover_cap_hours' => 40,
                    'is_active' => true,
                ]
            );
        }

        $ptoTypes = TradePtoType::where('tenant_id', $tenant->id)->get();
        foreach ($techs as $tech) {
            foreach ($ptoTypes as $type) {
                TradePtoBalance::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => $tech->id,
                        'trade_pto_type_id' => $type->id,
                    ],
                    [
                        'balance_hours' => 40,
                        'last_accrued_at' => now()->subDays(10),
                    ]
                );
            }
        }

        $jobSeeds = [
            ['summary' => 'HVAC Tune-up', 'status' => 'open', 'type' => 'service'],
            ['summary' => 'Water Heater Replacement', 'status' => 'in_progress', 'type' => 'service'],
            ['summary' => 'Maintenance Plan Follow-up', 'status' => 'scheduled', 'type' => 'service'],
            ['summary' => 'Electrical Panel Upgrade', 'status' => 'completed', 'type' => 'project'],
            ['summary' => 'Seasonal Safety Inspection', 'status' => 'closed', 'type' => 'service'],
        ];

        $jobs = [];
        foreach ($jobSeeds as $index => $seed) {
            $contact = $contacts[$index % count($contacts)];
            $location = $serviceLocations[$index % count($serviceLocations)];
            $createdAt = now()->subDays(29 - ($index * 4))->setTime(9, 0);

            $job = TradeJob::firstOrCreate(
                ['tenant_id' => $tenant->id, 'summary' => $seed['summary']],
                [
                    'client_id' => $contact->id,
                    'company_id' => $company->id,
                    'service_location_id' => $location->id,
                    'type' => $seed['type'],
                    'status' => $seed['status'],
                    'description' => $seed['summary'] . ' for ' . $contact->firstName,
                ]
            );

            if ($job->wasRecentlyCreated) {
                $job->timestamps = false;
                $job->created_at = $createdAt;
                $job->updated_at = $createdAt->copy()->addDays(1);
                $job->save();
            }

            if ($job->items()->count() === 0) {
                $job->items()->createMany([
                    [
                        'tenant_id' => $tenant->id,
                        'description' => 'Labor',
                        'quantity' => 2,
                        'unit_price' => 120,
                        'line_total' => 240,
                        'sort_order' => 0,
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'description' => 'Materials',
                        'quantity' => 1,
                        'unit_price' => 180,
                        'line_total' => 180,
                        'sort_order' => 1,
                    ],
                ]);
            }

            $jobs[] = $job;
        }

        $appointments = [];
        foreach ($jobs as $index => $job) {
            $count = $index % 2 === 0 ? 2 : 1;
            for ($i = 0; $i < $count; $i++) {
                $start = now()->subDays(25 - ($index * 3) - $i)->setTime(8 + $i, 0);
                if ($index === 0 && $i === 1) {
                    $start = now()->addDays(3)->setTime(10, 0);
                }
                $end = $start->copy()->addHours(2);
                $status = $start->isFuture() ? 'scheduled' : ($i === 0 ? 'completed' : 'canceled');

                $appointment = TradeAppointment::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'trade_job_id' => $job->id, 'start_at' => $start],
                    [
                        'end_at' => $end,
                        'status' => $status,
                        'notes' => 'Auto-seeded appointment',
                    ]
                );

                $appointments[] = $appointment;
                if ($status === 'completed') {
                    $appointment->timestamps = false;
                    $appointment->updated_at = $end->copy()->subMinutes(5);
                    $appointment->save();
                    $appointment->timestamps = true;
                }

                $assignedTechs = collect($techs)->shuffle()->take(2)->values();
                foreach ($assignedTechs as $tech) {
                    $assignment = AppointmentAssignment::firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'appointment_id' => $appointment->id,
                            'user_id' => $tech->id,
                        ],
                        [
                            'role' => $tech->id === $assignedTechs->first()->id ? 'lead' : 'helper',
                            'presence_status' => $status === 'completed' ? 'done' : 'assigned',
                            'on_site_at' => $status === 'completed' ? $start->copy()->addMinutes(10) : null,
                            'done_at' => $status === 'completed' ? $end->copy()->subMinutes(5) : null,
                        ]
                    );

                    if ($status === 'completed') {
                        TradeJobTimer::firstOrCreate(
                            [
                                'tenant_id' => $tenant->id,
                                'appointment_id' => $appointment->id,
                                'trade_job_id' => $job->id,
                                'user_id' => $tech->id,
                                'started_at' => $start->copy()->addMinutes(15),
                            ],
                            [
                                'ended_at' => $end->copy()->subMinutes(10),
                            ]
                        );
                    }
                }
            }
        }

        $unscheduledJob = TradeJob::firstOrCreate(
            ['tenant_id' => $tenant->id, 'summary' => 'Emergency Plumbing Visit'],
            [
                'client_id' => $contacts[0]->id,
                'company_id' => $company->id,
                'service_location_id' => $serviceLocations[0]->id,
                'type' => 'service',
                'status' => 'open',
                'description' => 'Unscheduled emergency call.',
            ]
        );

        if ($unscheduledJob->wasRecentlyCreated) {
            $unscheduledJob->timestamps = false;
            $unscheduledJob->created_at = now()->subDays(3)->setTime(11, 0);
            $unscheduledJob->updated_at = now()->subDays(2)->setTime(12, 0);
            $unscheduledJob->save();
        }

        foreach ($techs as $tech) {
            for ($i = 0; $i < 6; $i++) {
                $start = now()->subDays(28 - ($i * 4))->setTime(8, 0);
                $end = $start->copy()->addHours(8);

                TechShift::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => $tech->id,
                        'clock_in_at' => $start,
                    ],
                    [
                        'clock_out_at' => $end,
                        'shift_type' => 'work',
                        'notes' => 'Seeded shift',
                    ]
                );
            }
        }

        $leadSeeds = [
            [
                'name' => 'Avery Harper',
                'status' => 'new',
                'source' => 'website',
                'created_at' => now()->subDays(2)->setTime(9, 15),
                'contacted_at' => null,
            ],
            [
                'name' => 'Riley Quinn',
                'status' => 'contacted',
                'source' => 'manual',
                'created_at' => now()->subDays(5)->setTime(10, 10),
                'contacted_at' => now()->subDays(5)->setTime(11, 0),
            ],
            [
                'name' => 'Morgan Ruiz',
                'status' => 'scheduled',
                'source' => 'website',
                'created_at' => now()->subDays(12)->setTime(8, 45),
                'contacted_at' => now()->subDays(12)->setTime(9, 30),
            ],
            [
                'name' => 'Casey Patel',
                'status' => 'won',
                'source' => 'import',
                'created_at' => now()->subDays(18)->setTime(13, 20),
                'contacted_at' => now()->subDays(18)->setTime(14, 5),
                'won_at' => now()->subDays(16)->setTime(15, 0),
            ],
            [
                'name' => 'Jordan Bell',
                'status' => 'lost',
                'source' => 'website',
                'created_at' => now()->subDays(22)->setTime(10, 50),
                'contacted_at' => now()->subDays(22)->setTime(12, 10),
                'lost_at' => now()->subDays(20)->setTime(9, 0),
            ],
        ];

        foreach ($leadSeeds as $seed) {
            [$firstName, $lastName] = array_pad(explode(' ', $seed['name'], 2), 2, '');
            $email = Str::slug($seed['name'], '.') . '@leads.test';
            $leadData = [
                'tenant_id' => $tenant->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $seed['name'],
                'email' => $email,
                'phone' => '555-555-' . random_int(1000, 9999),
                'status' => $seed['status'],
            ];

            if (Schema::hasColumn('leads', 'source')) {
                $leadData['source'] = $seed['source'];
            }
            if (Schema::hasColumn('leads', 'source_detail')) {
                $leadData['source_detail'] = [
                    'utm_source' => $seed['source'] === 'website' ? 'google' : 'referral',
                    'utm_medium' => $seed['source'] === 'website' ? 'cpc' : 'email',
                    'page_url' => 'https://renlo.test/lead',
                ];
            }
            if (Schema::hasColumn('leads', 'captured_at')) {
                $leadData['captured_at'] = $seed['created_at'];
            }
            if (Schema::hasColumn('leads', 'first_contacted_at')) {
                $leadData['first_contacted_at'] = $seed['contacted_at'];
            }
            if (Schema::hasColumn('leads', 'scheduled_at') && $seed['status'] === 'scheduled') {
                $leadData['scheduled_at'] = $seed['created_at']->copy()->addDays(2)->setTime(9, 0);
            }
            if (Schema::hasColumn('leads', 'won_at') && !empty($seed['won_at'])) {
                $leadData['won_at'] = $seed['won_at'];
            }
            if (Schema::hasColumn('leads', 'lost_at') && !empty($seed['lost_at'])) {
                $leadData['lost_at'] = $seed['lost_at'];
            }
            if (Schema::hasColumn('leads', 'value_cents')) {
                $leadData['value_cents'] = random_int(25000, 180000);
            }
            if (Schema::hasColumn('leads', 'assigned_to_user_id')) {
                $leadData['assigned_to_user_id'] = $admin->id;
            }

            $lead = Lead::updateOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $email],
                $leadData
            );

            if ($lead->created_at->ne($seed['created_at'])) {
                $lead->timestamps = false;
                $lead->created_at = $seed['created_at'];
                $lead->updated_at = $seed['created_at']->copy()->addHours(1);
                $lead->save();
                $lead->timestamps = true;
            }

            if (Schema::hasTable('trade_lead_events')) {
                TradeLeadEvent::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'lead_id' => $lead->id,
                        'type' => 'created',
                    ],
                    [
                        'user_id' => $admin->id,
                        'payload_json' => ['source' => $seed['source']],
                    ]
                );

                if ($seed['contacted_at']) {
                    TradeLeadEvent::firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'lead_id' => $lead->id,
                            'type' => 'contacted',
                        ],
                        [
                            'user_id' => $admin->id,
                            'payload_json' => ['at' => $seed['contacted_at']->toDateTimeString()],
                        ]
                    );
                }
            }
        }

        $approvedTech = $techs[0] ?? null;
        $pendingTech = $techs[1] ?? null;
        $ptoType = $ptoTypes->first();

        if ($approvedTech && $ptoType) {
            $request = TradePtoRequest::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $approvedTech->id,
                    'trade_pto_type_id' => $ptoType->id,
                    'start_date' => now()->subDays(12)->toDateString(),
                ],
                [
                    'end_date' => now()->subDays(11)->toDateString(),
                    'hours_requested' => 8,
                    'status' => 'pending',
                    'requested_at' => now()->subDays(13),
                    'notes' => 'Seeded PTO request',
                ]
            );

            if ($request->status !== 'approved') {
                app(TradePtoService::class)->approveRequest($request, $admin->id);
            }
        }

        if ($pendingTech && $ptoType) {
            TradePtoRequest::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $pendingTech->id,
                    'trade_pto_type_id' => $ptoType->id,
                    'start_date' => now()->addDays(5)->toDateString(),
                ],
                [
                    'end_date' => now()->addDays(6)->toDateString(),
                    'hours_requested' => 12,
                    'status' => 'pending',
                    'requested_at' => now()->subDays(1),
                    'notes' => 'Seeded pending PTO request',
                ]
            );
        }

        $quoteSeeds = [
            ['title' => 'Seasonal HVAC Tune-up', 'status' => 'sent'],
            ['title' => 'Water Heater Install', 'status' => 'accepted'],
            ['title' => 'Electrical Safety Review', 'status' => 'draft'],
        ];

        foreach ($quoteSeeds as $index => $seed) {
            $contact = $contacts[$index % count($contacts)];
            $job = $jobs[$index % count($jobs)];

            $quote = TradeQuote::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'title' => $seed['title'],
                ],
                [
                    'client_id' => $contact->id,
                    'company_id' => $company->id,
                    'trade_job_id' => $job->id,
                    'status' => $seed['status'],
                    'subtotal' => 450,
                    'tax_rate' => 8.5,
                    'tax_total' => 38.25,
                    'discount_type' => 'percent',
                    'discount_value' => 5,
                    'discount_total' => 22.5,
                    'total' => 465.75,
                    'sent_at' => $seed['status'] !== 'draft' ? now()->subDays(4) : null,
                ]
            );

            if ($quote->items()->count() === 0) {
                TradeQuoteItem::create([
                    'tenant_id' => $tenant->id,
                    'trade_quote_id' => $quote->id,
                    'description' => 'Service labor',
                    'quantity' => 1,
                    'unit_price' => 450,
                    'line_total' => 450,
                    'sort_order' => 0,
                ]);
            }
        }

        foreach ($jobs as $index => $job) {
            if ($index > 2) {
                continue;
            }
            if (Invoice::where('tenant_id', $tenant->id)->where('trade_job_id', $job->id)->exists()) {
                continue;
            }

            $invoiceNumber = 'TRD-' . $tenant->id . '-' . Str::upper(Str::random(6));
            $total = (float) $job->items()->sum('line_total');

            Invoice::create([
                'tenant_id' => $tenant->id,
                'contact_id' => $job->client_id,
                'trade_job_id' => $job->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => now()->subDays(5),
                'due_date' => now()->addDays(15),
                'status' => 'sent',
                'total_amount' => $total,
                'total' => $total,
                'subtotal' => $total,
            ]);
        }

        $invoices = Invoice::where('tenant_id', $tenant->id)->limit(2)->get();
        foreach ($invoices as $index => $invoice) {
            if (InvoicePayment::where('tenant_id', $tenant->id)->where('invoice_id', $invoice->id)->exists()) {
                continue;
            }
            $paidAt = now()->subDays(7 - $index);
            InvoicePayment::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount ?? $invoice->total ?? 0,
                'method' => 'manual',
                'reference' => 'SEED-' . Str::upper(Str::random(6)),
                'paid_at' => $paidAt,
            ]);
        }

        $ledgerExists = TradePtoLedger::where('tenant_id', $tenant->id)->exists();
        if (!$ledgerExists) {
            foreach ($techs as $tech) {
                foreach ($ptoTypes as $type) {
                    TradePtoLedger::create([
                        'tenant_id' => $tenant->id,
                        'user_id' => $tech->id,
                        'trade_pto_type_id' => $type->id,
                        'hours' => 8,
                        'reason' => 'accrual',
                        'note' => 'Seeded accrual',
                    ]);
                }
            }
        }
    }
}
