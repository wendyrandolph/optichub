<div class="pricing-shell">
    <article class="pricing-card">
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

        <div class="pricing-body">
            <ul class="pricing-list">
                <li><span class="pricing-check"></span><span>Projects, clients, portal, billing, and files.</span></li>
                <li><span class="pricing-check"></span><span>14-day free trial — no credit card required.</span></li>
                <li><span class="pricing-check"></span><span>Month-to-month billing via Stripe.</span></li>
                <li><span class="pricing-check"></span><span>Unlimited clients, projects, and invoices.</span></li>
            </ul>
        </div>

        <footer class="pricing-footer">
            <div class="pricing-cta-row">
                <a class="btn btn--primary btn--lg w-full sm:w-auto" href="{{ route('trial.show') }}">
                    Start free trial
                </a>

                <button type="button" class="btn btn--secondary btn--lg w-full sm:w-auto"
                    onclick="document.getElementById('demo').scrollIntoView({ behavior: 'smooth' })">
                    Book a demo
                </button>
            </div>

            <p class="pricing-guarantee">
                <span class="dot"></span>
                Secure checkout. Cancel anytime.
            </p>
        </footer>
    </article>
</div>
