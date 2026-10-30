<?php
get_header();
$error = isset($_GET['error']);
$denied = isset($_GET['denied']);
?>
<main class="lk-login-page">
  <section class="lk-login-stage" aria-hidden="true">
    <strong>LK Tech</strong>
    <div>
      <h1>Кабинет заявок</h1>
      <p>Сайт и Telegram в одном потоке. Новые заявки появляются сами.</p>
    </div>
    <span>CRM</span>
  </section>

  <section class="lk-login-panel">
    <form class="lk-login" method="post" action="">
      <h2>Вход</h2>
      <p>Только администраторы и менеджеры.</p>

      <?php if ($denied) : ?>
        <div class="lk-alert is-danger" role="alert">У этого аккаунта нет доступа.</div>
      <?php elseif ($error) : ?>
        <div class="lk-alert" role="alert">Логин или пароль не подошли.</div>
      <?php endif; ?>

      <div class="lk-field">
        <label for="log">Логин или email</label>
        <input id="log" type="text" name="log" required autocomplete="username">
      </div>
      <div class="lk-field">
        <label for="pwd">Пароль</label>
        <input id="pwd" type="password" name="pwd" required autocomplete="current-password">
      </div>
      <label class="lk-check">
        <input type="checkbox" name="rememberme" value="1">
        Запомнить меня
      </label>
      <?php wp_nonce_field('lktech_login'); ?>
      <input type="hidden" name="lktech_login" value="1">
      <button class="lk-btn" type="submit">Войти</button>
    </form>
  </section>
</main>
<?php get_footer(); ?>
