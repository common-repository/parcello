<?php

/**
 * This is the Overview of parcello
 * It renders
 */
class ParcelloOverView {
  private $parcello;

  public function __construct($parcello) {
    $this->parcello = $parcello;
  }

  /**
   * Renders the Overview Page HTML
   * @param $parcello Parcello
   */
  public function render() {
?>
    <div class="parcello__canvas parcello__canvas--big">
      <h2 class="parcello__title">
        <?php echo __('Overview', 'parcello') ?>
      </h2>

      <?php
      $email_settings = $this->parcello->api->get_email_settings();
      if ($email_settings->data->domain_status == "unverified") {
      ?>
        <div class="parcello__information">
          <?php echo __('Domain is not verified', 'parcello') ?>
          <br />
          <?php echo __('That means that emails will not been sent automatically', 'parcello') ?>
          <br>
          <a href="https://business.parcello.org/profile/email">
            <?php echo __('Verify Domain', 'parcello') ?>
          </a>
        </div>
      <?php
      }
      ?>

      <div class="parcello__space parcello__space--25"></div>

      <a class="parcello__button" href="https://business.parcello.org">Zum Parcello Dashboard</a>
    </div>
<?php
  }
}
