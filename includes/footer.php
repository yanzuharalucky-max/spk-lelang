</main>

<footer class="site-footer premium-footer">
    <div class="footer-blur footer-blur-1"></div>
    <div class="footer-blur footer-blur-2"></div>
    <div class="footer-grid-line"></div>

    <div class="container footer-grid">
        <div class="footer-col footer-brand-col footer-reveal-left">
            <div class="footer-brand">
                <div class="footer-logo-wrap">
                    <img src="<?= BASE_URL; ?>/assets/img/logo-saleno.png" alt="<?= e(t('footer_brand_title')); ?>" class="footer-logo">
                </div>
                <div>
                    <h3><?= e(t('footer_brand_title')); ?></h3>
                    <p><?= e(t('footer_brand_desc')); ?></p>
                </div>
            </div>
        </div>

        <div class="footer-col footer-nav-col footer-reveal-up">
            <h4 class="footer-nav-title"><?= e(t('footer_nav_title')); ?></h4>

            <div class="footer-nav-list">
                <a href="<?= langUrl('/index.php'); ?>" class="footer-nav-link">
                    <span class="footer-nav-dot"></span>
                    <span><?= e(t('footer_nav_home')); ?></span>
                </a>

                <a href="<?= langUrl('/index.php', [], 'listing-section'); ?>" class="footer-nav-link">
                    <span class="footer-nav-dot"></span>
                    <span><?= e(t('footer_nav_listing')); ?></span>
                </a>

                <a href="<?= langUrl('/auth/login.php'); ?>" class="footer-nav-link">
                    <span class="footer-nav-dot"></span>
                    <span><?= e(t('footer_nav_login')); ?></span>
                </a>

                <a href="<?= langUrl('/auth/register.php'); ?>" class="footer-nav-link">
                    <span class="footer-nav-dot"></span>
                    <span><?= e(t('footer_nav_register')); ?></span>
                </a>
            </div>
        </div>

        <div class="footer-col footer-contact-col footer-reveal-right">
            <h4 class="footer-contact-title"><?= e(t('footer_contact_title')); ?></h4>

            <div class="footer-contact-list">
                <div class="footer-contact-item">
                    <span class="footer-contact-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1-.24 11.36 11.36 0 0 0 3.56.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.49a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.56 1 1 0 0 1-.24 1l-2.2 2.23Z"></path>
                        </svg>
                    </span>
                    <div class="footer-contact-text">
                        <a href="tel:+622150207737">021-5020 7737</a>
                    </div>
                </div>

                <div class="footer-contact-item">
                    <span class="footer-contact-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 6.75h16A1.25 1.25 0 0 1 21.25 8v8A1.25 1.25 0 0 1 20 17.25H4A1.25 1.25 0 0 1 2.75 16V8A1.25 1.25 0 0 1 4 6.75Zm0 1.5a.2.2 0 0 0-.2.2v.16l8.2 5.32 8.2-5.32v-.16a.2.2 0 0 0-.2-.2H4Zm16.2 2.14-7.79 5.06a.75.75 0 0 1-.82 0L3.8 10.39V16c0 .11.09.2.2.2h16c.11 0 .2-.09.2-.2v-5.61Z"></path>
                        </svg>
                    </span>
                    <div class="footer-contact-text">
                        <a href="mailto:sales.ar@seleno.id">sales.ar@seleno.id</a>
                    </div>
                </div>

                <div class="footer-contact-item footer-contact-item-address">
                    <span class="footer-contact-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2.75A6.25 6.25 0 0 0 5.75 9c0 4.75 5.2 10.72 5.42 10.97a1.1 1.1 0 0 0 1.66 0c.22-.25 5.42-6.22 5.42-10.97A6.25 6.25 0 0 0 12 2.75Zm0 8.5A2.25 2.25 0 1 1 14.25 9 2.25 2.25 0 0 1 12 11.25Z"></path>
                        </svg>
                    </span>
                    <div class="footer-contact-text">
                        <span><?= e(t('footer_address')); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container copyright footer-reveal-up delay-footer">
        &copy; <?= date('Y'); ?> <?= e(t('footer_copyright')); ?>
    </div>
</footer>

</body>
</html>