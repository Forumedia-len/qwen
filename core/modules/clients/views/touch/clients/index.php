<?php
/**
 * @var string           $form
 * @var string           $title
 * @var string           $messages
 * @var RegistrationView $this
 */

use AC\core\modules\clients\views\RegistrationView;
?>
<!--<time data-time="60000"/>-->
<?php
echo $this->render(Service::engines()->getTypeAlias(), array('form' => $form, 'title' => $title, 'messages' => $messages))
?>

