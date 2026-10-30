<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$user = wp_get_current_user();
$logout = wp_logout_url(home_url('/login/'));
?>
<div class="lk-app lk-app-settings is-show-list" data-settings>
  <aside class="lk-rail" aria-label="Навигация">
    <a class="lk-rail-btn" href="<?php echo esc_url(home_url('/')); ?>" title="Кабинет" aria-label="Кабинет">
      <span class="lk-rail-ico" aria-hidden="true">LK</span>
    </a>
    <a class="lk-rail-btn" href="<?php echo esc_url(home_url('/')); ?>" title="Лента" aria-label="Лента">
      <span class="lk-rail-ico" aria-hidden="true">☰</span>
    </a>
    <div class="lk-rail-spacer"></div>
    <a class="lk-rail-btn is-active" href="<?php echo esc_url(home_url('/settings/')); ?>" title="Настройки" aria-label="Настройки">
      <span class="lk-rail-ico" aria-hidden="true">⚙</span>
    </a>
    <a class="lk-rail-btn" href="<?php echo esc_url($logout); ?>" title="Выйти" aria-label="Выйти">
      <span class="lk-rail-ico" aria-hidden="true">⎋</span>
    </a>
  </aside>

  <main class="lk-settings" data-settings-main>
    <header class="lk-settings-head">
      <div>
        <p class="lk-quiet" style="margin:0 0 4px">Личный кабинет</p>
        <h1>Настройки</h1>
      </div>
      <a class="lk-btn lk-btn-ghost" href="<?php echo esc_url(home_url('/')); ?>">К заявкам</a>
    </header>

    <div class="lk-settings-grid">
      <section class="lk-settings-card">
        <h2>Внешний вид</h2>
        <label class="lk-switch-row">
          <span>
            <strong>Тёмная тема</strong>
            <small>Контрастный тёмный интерфейс кабинета</small>
          </span>
          <input type="checkbox" data-setting="themeDark">
        </label>
        <label class="lk-switch-row">
          <span>
            <strong>Компактный режим</strong>
            <small>Меньше отступы в ленте и карточках</small>
          </span>
          <input type="checkbox" data-setting="compact">
        </label>
        <label class="lk-switch-row">
          <span>
            <strong>Уменьшить анимации</strong>
            <small>Отключить переходы и свайп-эффекты</small>
          </span>
          <input type="checkbox" data-setting="reduceMotion">
        </label>
      </section>

      <section class="lk-settings-card">
        <h2>Уведомления</h2>
        <label class="lk-switch-row">
          <span>
            <strong>Звук новой заявки</strong>
            <small>Короткий сигнал при появлении лида</small>
          </span>
          <input type="checkbox" data-setting="sound">
        </label>
        <label class="lk-switch-row">
          <span>
            <strong>Браузерные уведомления</strong>
            <small>Показывать системный toast при новой заявке</small>
          </span>
          <input type="checkbox" data-setting="desktopNotify">
        </label>
        <label class="lk-switch-row">
          <span>
            <strong>Автооткрытие новой заявки</strong>
            <small>Сразу открывать карточку свежего лида</small>
          </span>
          <input type="checkbox" data-setting="autoOpen">
        </label>
        <div class="lk-field">
          <label class="lk-field-label" for="lk-poll">Интервал опроса ленты</label>
          <select id="lk-poll" data-setting-select="pollMs">
            <option value="5000">5 секунд</option>
            <option value="8000">8 секунд</option>
            <option value="15000">15 секунд</option>
            <option value="30000">30 секунд</option>
          </select>
        </div>
      </section>

      <section class="lk-settings-card">
        <h2>Рабочий процесс</h2>
        <label class="lk-switch-row">
          <span>
            <strong>Только непрочитанные в ленте</strong>
            <small>Скрывать уже просмотренные заявки</small>
          </span>
          <input type="checkbox" data-setting="onlyUnread">
        </label>
        <label class="lk-switch-row">
          <span>
            <strong>Показывать бейдж непрочитанных</strong>
            <small>Точка и счётчик на иконке в рейле</small>
          </span>
          <input type="checkbox" data-setting="showUnreadBadge" checked>
        </label>
        <div class="lk-actions" style="margin-top:8px">
          <button type="button" class="lk-btn lk-btn-sm" data-mark-all-read>Отметить все прочитанными</button>
          <button type="button" class="lk-btn lk-btn-ghost lk-btn-sm" data-clear-seen>Сбросить прочитанные</button>
        </div>
      </section>

      <section class="lk-settings-card">
        <h2>Аккаунт</h2>
        <p class="lk-quiet" style="margin-top:0">Вы вошли как <strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong></p>
        <div class="lk-actions">
          <a class="lk-btn lk-btn-ghost" href="<?php echo esc_url(home_url('/')); ?>">Открыть кабинет</a>
          <a class="lk-btn lk-btn-danger" href="<?php echo esc_url($logout); ?>">Выйти</a>
        </div>
      </section>
    </div>
  </main>
</div>

<div class="lk-toast" data-toast aria-live="polite"></div>
<?php get_footer(); ?>
