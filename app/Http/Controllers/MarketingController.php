<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketingController extends Controller
{
  /** @var array<string,string> */
  private array $pages = [
    'marketing/home'     => 'marketing.home',
    'marketing/features' => 'marketing.features',
    'marketing/pricing'  => 'marketing.pricing',
    'marketing/faq'      => 'marketing.faq',
    'marketing/about'    => 'marketing.about',
  ];

  public function page(Request $request): View
  {
    $key = (string) $request->route('pageName');
    if (!isset($this->pages[$key])) {
      throw new NotFoundHttpException();
    }
    return view($this->pages[$key]);
  }



  public function contactForm(): View
  {
    return view('marketing.contact');
  }

  public function submitContact(Request $request)
  {
    $data = $request->validate([
      'name'    => ['required', 'string', 'max:255'],
      'email'   => ['required', 'email', 'max:255'],
      'message' => ['required', 'string', 'max:5000'],
    ]);

    // TODO: queue email or save inquiry
    return back()->with('status', 'Thanks! We’ll be in touch.');
  }
}
