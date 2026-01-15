  <div class="pricing-shell">
      <article class="pricing-card">
          {{-- Top pill + amount --}}
          <header class="pricing-card__header">
              <span class="pricing-tag">Solo workspace</span>

              <div class="pricing-main">
                  <div class="pricing-price">
                      <span class="pricing-currency">$</span>
                      <span class="pricing-amount">49</span>
                      <span class="pricing-per">/month</span>
                  </div>
                  <p class="pricing-subcopy">
                      Full access to Renlo for your studio. Cancel anytime.
                  </p>
              </div>
          </header>

          {{-- Bullets --}}
          <div class="pricing-body">
              <ul class="pricing-list">
                  <li>
                      <span class="pricing-check"></span>
                      <span>All core features unlocked — projects, clients, portal, billing.</span>
                  </li>
                  <li>
                      <span class="pricing-check"></span>
                      <span>14-day free trial — no credit card required to explore.</span>
                  </li>
                  <li>
                      <span class="pricing-check"></span>
                      <span>Month-to-month billing via Stripe. No contracts.</span>
                  </li>
                  <li>
                      <span class="pricing-check"></span>
                      <span>Unlimited clients, projects, and invoices in your workspace.</span>
                  </li>
              </ul>
          </div>

          {{-- CTA row --}}
          <footer class="pricing-footer">
              <div class="pricing-cta-row">
                  <a class="btn btn--primary" href="{{ route('trial.show') }}">
                      Start your 14-day free trial
                  </a>
                  <button type="button" class="btn btn--ghost pricing-demo-btn"
                      onclick="document.getElementById('demo').scrollIntoView({ behavior: 'smooth' })">
                      Book a demo
                  </button>
              </div>

              <p class="pricing-guarantee">
                  <span class="dot"></span>
                  Secure Stripe checkout. You keep your data if you ever leave.
              </p>
          </footer>
      </article>
  </div>
