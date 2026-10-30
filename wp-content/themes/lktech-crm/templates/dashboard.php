<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$user = wp_get_current_user();
$logout = wp_logout_url(home_url('/login/'));
$initial_lead = (int) ($_GET['lead'] ?? 0);
$settings_url = home_url('/settings/');
?>
<div class="lk-app is-show-list" data-dashboard data-initial-lead="<?php echo (int) $initial_lead; ?>">
  <aside class="lk-rail" aria-label="Навигация">
    <a class="lk-rail-btn is-active" href="<?php echo esc_url(home_url('/')); ?>" title="Кабинет" aria-label="Кабинет">
      <span class="lk-rail-ico" aria-hidden="true">LK</span>
    </a>
    <button type="button" class="lk-rail-btn" data-focus-list title="Лента заявок" aria-label="Лента">
      <span class="lk-rail-ico" aria-hidden="true">☰</span>
    </button>
    <button type="button" class="lk-rail-btn" data-filter-unread title="Непрочитанные" aria-label="Непрочитанные">
      <span class="lk-rail-ico" aria-hidden="true">◎</span>
      <span class="lk-rail-badge" data-unread-badge hidden>0</span>
    </button>
    <button type="button" class="lk-rail-btn" data-refresh-feed title="Обновить" aria-label="Обновить">
      <span class="lk-rail-ico" aria-hidden="true">↻</span>
    </button>
    <button type="button" class="lk-rail-btn" data-sound-toggle title="Звук" aria-label="Звук" aria-pressed="false">
      <span class="lk-rail-ico" data-sound-ico aria-hidden="true">♪</span>
    </button>
    <div class="lk-rail-spacer"></div>
    <a class="lk-rail-btn" href="<?php echo esc_url($settings_url); ?>" title="Настройки" aria-label="Настройки">
      <span class="lk-rail-ico" aria-hidden="true">⚙</span>
    </a>
    <a class="lk-rail-btn" href="<?php echo esc_url($logout); ?>" title="Выйти" aria-label="Выйти">
      <span class="lk-rail-ico" aria-hidden="true">⎋</span>
    </a>
  </aside>

  <div class="lk-stage" data-stage>
    <div class="lk-stage-track" data-stage-track>
      <aside class="lk-side">
        <div class="lk-side-head">
          <strong>Заявки</strong>
          <button type="button" class="lk-sound-toggle" data-sound-toggle aria-pressed="false">Звук выкл</button>
        </div>

        <div class="lk-metrics" data-stats>
          <div class="lk-metric"><b data-stat="new">—</b><span>новые</span></div>
          <div class="lk-metric"><b data-stat="today">—</b><span>сегодня</span></div>
          <div class="lk-metric"><b data-stat="in_progress">—</b><span>в работе</span></div>
          <div class="lk-metric"><b data-stat="total">—</b><span>всего</span></div>
        </div>

        <div class="lk-filters">
          <input type="search" placeholder="Поиск" data-filter="s" aria-label="Поиск">
          <?php
          lktech_crm_render_select('filter_status', lktech_crm_status_labels(), 'Статус', '', 'lk-select-filter');
          lktech_crm_render_select('filter_source', lktech_crm_source_labels(), 'Источник', '', 'lk-select-filter');
          ?>
        </div>

        <div class="lk-lead-list" data-list>
          <div class="lk-empty-mini">Загружаем…</div>
        </div>
      </aside>

      <main class="lk-main" data-main>
        <div class="lk-main-empty" data-empty>
          <div>
            <strong>Выберите заявку</strong>
            Слева лента с сайта и из Telegram. Новые появляются сами.
          </div>
        </div>
        <div data-detail hidden></div>
      </main>

      <aside class="lk-aside" data-aside hidden></aside>
    </div>
  </div>

  <nav class="lk-mobile-bar" aria-label="Мобильная навигация">
    <button type="button" class="is-active" data-mobile-view="list">Лента</button>
    <button type="button" data-mobile-view="detail">Карточка</button>
    <button type="button" data-mobile-view="tools">Действия</button>
  </nav>
</div>

<div class="lk-toast" data-toast aria-live="polite"></div>

<div hidden>
  <span data-user-name><?php echo esc_html($user->display_name); ?></span>
  <a data-logout href="<?php echo esc_url($logout); ?>"></a>
</div>
<?php get_footer(); ?>
