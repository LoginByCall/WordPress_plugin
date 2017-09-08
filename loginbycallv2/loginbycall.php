<?php

/*
  Plugin Name: LoginByCall
  Plugin URI:
  Description: LoginByCall
  Version: 0.01
  Author: 0
  Author URI: 0
 */

require_once dirname(__FILE__) . '/function.php';
add_action('admin_menu', 'add_loginbycall_page');
function add_loginbycall_page()
{
    add_menu_page('LoginByCall', 'LoginByCall', 'manage_options', 'loginbycall', 'loginbycall_options_page', 'dashicons-admin-generic', 1001);
}

function ajax_login_init()
{
    wp_register_script('ajax-login-script', plugin_dir_url(__FILE__) . '/loginbycall.js', array('jquery'));
    wp_enqueue_script('ajax-login-script');
    wp_localize_script('ajax-login-script', 'ajax_login_object', array(

        'ajaxurl' => admin_url('admin-ajax.php'),
        'redirecturl' => home_url(),
        'loadingmessage' => __('Sending user info, please wait...')
    ));
    wp_enqueue_style('custom-login', plugin_dir_url(__FILE__) . '/css/loginbycall.css');
}

add_action('login_enqueue_scripts', 'ajax_login_init');

//создание глобальных перемееных для хранения настроек loginbycall
function loginbycall_options_page()
{
    loginbycall_change_options();
}

//редактированние настроек loginbycall
function loginbycall_change_options()
{

    if (isset($_POST['email_notification']) && $_POST['email_notification'] != getNotificationEmail()) {

        change_credential($_POST['email_notification']);

    }
    if (isset($_POST['loginbycall_update_key_btn']) && get_option('loginbycall_api_key') != $_POST['loginbycall_update_key_btn']) {
        update_option('loginbycall_api_key', $_POST['api_key']);
        if (get_option('loginbycall_new_api_id')) {
            update_option('loginbycall_api_id', get_option('loginbycall_new_api_id'));
            delete_option('loginbycall_new_api_id');
        }
        credential_confirm();
    }

    if (isset($_POST['loginbycall_base_setup_btn'])) {
        $limit = options_loginbycall($_POST['email_notification_limit']);

        if (isset($_POST['loginbycall_register_phone']))
            $loginbycall_register_phone = $_POST['loginbycall_register_phone'];
        else
            $loginbycall_register_phone = 0;
        update_option('loginbycall_register_phone', $loginbycall_register_phone);
        loginbycall_update_roles(array('_onefactor', '_twofactor'));

    }

    if (isset($_POST['loginbycall_delete_info'])) {
        require_once dirname(__FILE__) . '/template/delete-info.tpl.php';
        echo '<link href="' . plugins_url('css/loginbycall.css', __FILE__) . '" rel="stylesheet" type="text/css" />';
    }
    //рендер формы настроек LoginByCall
    if (isset($_POST['loginbycall_pay_btn'])) {
        echo pay_loginbycall($_POST['pay']);
    } else
        render_settings_loginbycall();

    //var_dump(status_loginbycall());
}

function render_settings_loginbycall()
{
    wp_enqueue_script('jquery-ui-dialog'); // jquery and jquery-ui should be dependencies, didn't check though...
    wp_enqueue_style('wp-jquery-ui-dialog');
    if (server_status() == 1)
        $balance = balance_loginbycall();
    else
        $balance = 0;

    echo '<h2>' . __('LoginByCall Settings', 'loginbycall') . '</h2>';
    if (get_option('loginbycall_credential_active') != 1)
        echo '<div class="notice notice-error"><p>' . __('Ключи не активны', 'loginbycall') . '</p></div>';
    echo '<form id="loginbycallupdateform" method="post" action="' . $_SERVER['PHP_SELF'] . '?page=loginbycall&updated=true">';
    echo '<table class="form-table">
			<tr>
				<th><label>' . __('Api key', 'loginbycall') . '</label></th>
				<td width="325"><input name="api_key" type="text" style="width: 323px;" value="' . get_option('loginbycall_api_key') . '"/><br><span class="description">' . __('Enter your api key', 'loginbycall') . '</span></td>
				<td style="vertical-align:top;"><input type="submit" name="loginbycall_update_key_btn" class="button button-primary" value="Подтвердить ключ" /></td>
				<td width="900"></td>
			</tr>
			<tr>
			<th><label>Баланс LoginByCall</label></th>
			<td  class="admin_balance" >
			    <div style="float:left; line-height:2.2;" id="js-loginbycall-balance">' . (lbc_get_safe($balance, 'balance') - lbc_get_safe($balance, 'consumed')) . '</div><div style="float:left; line-height:2.2;  margin-right:5px;">&nbsp;кредитов</div>
			    <div style="padding-top:5px; float:left; margin-right:5px;"><a href="#" id="js-loginbycall-update"><img src="' . plugin_dir_url(__FILE__) . 'img/refresh.png" style="width:20px;"></a></div>
            </td>
            <td><a href="http://loginbycall.com" target="_blank">Тарифы</a></td>
			</tr>
			<tr>
			<th>Пополнить баланс</th>
			<td width="300"><input name="pay" value="10000">  <br><span class="description">Укажите сумму пополнения в кредитах</span></td>
			<td style="vertical-align:top;"><input type="submit" name="loginbycall_pay_btn" class="button button-primary" value="Пополнить баланс" /></td>
			<td></td>
			</tr>
			<tr>
			<th><label>Уведомлять о снижении баланса</label></th>
			<td>
			<div style="float:left"><input id="js_email_note" name="email_notification" value="' . getNotificationEmail() . '"><br><span class="description">E-mail для уведомлений</span></div>
			</td>
			<td>
			<div><input name="email_notification_limit" value="' . lbc_get_safe($balance, 'balance_notify_limit') . '"><br><span class="description">Уведомлять при балансе менее</span></div>
			</td>
			<td></td>
			</tr>
			<tr>
			<th><label>Требовать ввода телефона при регистрации</label></th>
			<td><input name="loginbycall_register_phone" value="1" type="checkbox" ' . (get_option('loginbycall_register_phone') == 1 ? 'checked="checked"' : '') . '></td>
			<td></td>
			<td></td>
			</tr>
			<tr>
			<th><label>Настройки по ролям</label></th>
			<td></td>
                                                <td class="b">Разрешить
                        безпарольную
                        авторизацию</td>
                                                <td class="b">Разрешить
                        двухфакторную
                        авторизацию</td>
                    </tr>';

    foreach (get_editable_roles() as $role_name => $role_info): ?>
        <tr>
            <td></td>
            <td class="b"><?php echo $role_name ?></td>
            <td><input name="loginbycall_<?php echo $role_name ?>_onefactor" type="checkbox"
                       value="1" <?php echo get_option('loginbycall_' . $role_name . '_onefactor') == 1 ? 'checked="checked"' : '' ?>>
            </td>
            <td><input name="loginbycall_<?php echo $role_name ?>_twofactor" type="checkbox"
                       value="1" <?php echo get_option('loginbycall_' . $role_name . '_twofactor') == 1 ? 'checked="checked"' : '' ?>>
            </td>
        </tr>
    <?php endforeach;
    echo '
			<tr>
				<td><input type="submit" name="loginbycall_base_setup_btn" class="button button-primary" /></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<div id="my-dialog" class="hidden" style="max-width:800px">
  <h3>Обратите внимание!</h3>
  <p>При смене email администратора домена происходит перевыпуск api_key, который придет на почту.</p>
  <p>Не забудьте обновить ключи сервиса</p>
</div>';
    echo '</form>';
    ?>
    <script>jQuery(document).ready(function (e) {
            jQuery("#js-loginbycall-update").click(function (e) {
                e.preventDefault;
                jQuery.post("<?php echo get_site_url(); ?>/wp-admin/admin-ajax.php?action=loginbycall_get_balance", function (data) {
                    jQuery("#js-loginbycall-balance").html(data);
                });
            });
            var notEmail = "<?php echo getNotificationEmail(); ?>";
            jQuery("#loginbycallupdateform").submit(function (e) {
                console.log(jQuery("#js_email_note").val() != notEmail);
                if (jQuery("#js_email_note").val() != notEmail) {
                    notEmail = jQuery("#js_email_note").val();
                    e.preventDefault();
                    jQuery("#my-dialog").dialog("open");
                }
            });
            // initalise the dialog
            jQuery("#my-dialog").dialog({
                title: "Предупреждение",
                dialogClass: "wp-dialog",
                autoOpen: false,
                draggable: false,
                width: "auto",
                modal: true,
                resizable: false,
                closeOnEscape: true,
                position: {
                    my: "center",
                    at: "center",
                    of: window
                },
                open: function () {
                    // close dialog by clicking the overlay behind it
                    jQuery(".ui-widget-overlay").bind("click", function () {
                        jQuery("#my-dialog").dialog("close");

                    })
                },
                create: function () {
                    // style fix for WordPress admin
                    jQuery(".ui-dialog-titlebar-close").addClass("ui-button");
                }
            }).on('dialogclose', function (event) {
                jQuery("#loginbycallupdateform").submit();
            });
            ;
            // bind a button or a link to open the dialog

        });</script>
    <style>.b {
            font-weight: 600;
        }</style>
<?php
}

function loginbycall_render_login_types($user)
{

    $allow = loginbycall_check_allowed_role($user->roles);
    $type = get_user_meta($user->ID, 'loginbycall_user_login_type', true);

    //если доступен только 1 способ он должен быть отмечен, если у юзера нет типа то первый доступный способ
    if ($type == false) {
        if ($allow['_onefactor'])
            $type = 1;
        elseif ($allow['_twofactor'])
            $type = 2;
    }
    if ($allow['_onefactor']) {
        ?>
        <div>
            <label for="user_login_type">
                <input type="radio" name="loginbycall_user_login_type" id="user_login_type"
                       value="1"<?php echo ($type == 1) ? 'checked="checked"' : ''
                ?>>Простой и безопасный (без пароля)
            </label>
        </div>
    <?php
    }
    if ($allow['_twofactor']) {
        ?>
        <div>
            <label for="user_login_type2">
                <input type="radio" name="loginbycall_user_login_type" id="user_login_type2"
                       value="2"<?php
                echo ($type == 2) ? 'checked="checked"' : ''
                ?>>Супербезопасный (двухфакторная)
            </label>
        </div>
    <?
    }
}


add_action('show_user_profile', 'my_show_extra_profile_fields');
add_action('edit_user_profile', 'my_show_extra_profile_fields');

function my_show_extra_profile_fields($user)
{

    ?>

    <h3>Настройки простой авторизации «LoginByCall»</h3>

    <table class="form-table">

        <tr>
            <th><label for="loginbycall_phone">Телефон</label></th>

            <td>

                +<input type="text" name="loginbycall_phone" id="loginbycall_phone"
                        value="<?php echo get_user_meta($user->ID, 'loginbycall_user_phone', true); ?>"
                        class="regular-text"/><br/>
            </td>
        </tr>
        <tr>
            <th><label for="loginbycall_user_factor">Входить без пароля</label></th>
            <td>
                <?php
                $allow = loginbycall_check_allowed_role($user->roles);
                $type = get_user_meta($user->ID, 'loginbycall_user_login_type', true);
                if ($type == false) {
                    if ($allow['_onefactor'])
                        $type = 1;
                    elseif ($allow['_twofactor'])
                        $type = 2;
                }
                ?>
                <input type="checkbox" name="loginbycall_user_factor" id="loginbycall_user_factor" value="1"
                    <?php echo $type == 1 ? 'checked' : '' ?>
                       class="regular-text"/><br/>
            </td>
        </tr>
    </table>
<?php
}

add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');

function my_save_extra_profile_fields($user_id)
{

    if (!current_user_can('edit_user', $user_id))
        return false;
    update_user_meta($user_id, 'loginbycall_user_phone', $_POST['loginbycall_phone']);
    if (isset($_POST['loginbycall_user_factor']))
        $factor = 1;
    else
        $factor = 2;
    $user = get_user_by('id', $user_id);
    $allow = loginbycall_check_allowed_role($user->roles);

    $type = null;
    if ($allow['_onefactor'] && $factor == 1)
        $type = 1;
    elseif ($allow['_twofactor'] && $factor == 2)
        $type = 2;
    if (isset($type))
        update_user_meta($user_id, 'loginbycall_user_login_type', $type);
}


//инсталяция плагина - создание тапблици и с страниц для работы loginbycall
function loginbycall_install()
{

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    register_loginbycall();
}


//хук срабатываюший при перезагрузки страници
function loginbycall_run()
{
    load_plugin_textdomain('loginbycall', false, basename(__DIR__) . '/i18n');
}


/**
 * Проверяет, есть ли уже созданный юзер с таким номером телефона
 * @param $phone
 * @return mixed если юзер найден false, иначе true
 */
function loginbycall_is_unique_phone($phone)
{
    $target_phone = $phone;

    global $wpdb;

    $lbc_user = $wpdb->get_results("SELECT user_id FROM wp_usermeta WHERE meta_key = 'loginbycall_user_phone' and meta_value = '$target_phone'", ARRAY_A);
    if (count($lbc_user)) {
        return false;
    }

    return true;
}


add_action('wp_enqueue_scripts', 'prefix_add_my_stylesheet');

function prefix_add_my_stylesheet()
{
    // Respects SSL, Style.css is relative to the current file
    wp_register_style('prefix-style', plugins_url('css/loginbycall.css', __FILE__));
    wp_enqueue_style('prefix-style');
}

/**
 * [loginbycall_settings]
 */
function function_loginbycall_settings_user($atts)
{

    global $user_ID;
    global $user_email;
    global $wpdb;

    $t = ''; // в $t будет код формы настроек

    if (is_user_logged_in()) { // Если ползователь авторизован

        $table_name = $wpdb->prefix . "loginbycall_user";

        if (count($_POST)) { // если юзер что-то менял в своих настройках

            if (!isset($_POST['user_allowed'])) { // TODO: check this condition. There is no 'user_allowed' input in the form
                $wpdb->update(
                    $table_name, array('status' => 0), array('uid' => $user_ID), array('%d'), array('%d')
                );
            } else {
                $wpdb->update(
                    $table_name, array('status' => 1), array('uid' => $user_ID), array('%d'), array('%d')
                );
            }

            if (isset($_POST['user_unbind'])) {
                $redirect_uri = get_option('loginbycall_redirect_uri');
                $client_id = get_option('loginbycall_client_id');
                $client_secret = get_option('loginbycall_client_secret');
                $refresh_token = get_one_field('refresh_token', $table_name, 'uid', $user_ID);
                $target_token = get_one_field('target_token', $table_name, 'uid', $user_ID);
                $context_access = stream_context_create(array(
                    'http' => array('method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                        'content' => "redirect_uri=$redirect_uri&client_id=$client_id&client_secret=$client_secret&grant_type=refresh_token&refresh_token=$refresh_token"
                    )));
                $answer = json_decode(@file_get_contents("https://loginbycall.com/oauth/token", false, $context_access));

                $context_access = stream_context_create(array(
                    'http' => array('method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                        'content' => "access_token=$answer->access_token&target_token=$target_token"
                    )));
                $answer = json_decode(@file_get_contents("https://loginbycall.com/api/oauth/v2/userinfo/destroy", false, $context_access));
                $wpdb->delete($table_name, array('uid' => $user_ID));
            }
        }
        // если юзер в таблице loginbycall
        if (get_one_field('uid', $table_name, 'uid', $user_ID)) {

            $i_user = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE uid = " . $user_ID); // находим ID юзера в таблице
            $i_user = $i_user[0];

            if (isset($_SESSION['loginbycall_create_account'])) {
                unset($_SESSION['loginbycall_create_account']);
                $t .= '<div class="loginbycall-message">' . __('You account has been successfully created.', 'loginbycall') . '</div>';
            }
            if (isset($_SESSION['loginbycall_bind_account'])) {
                unset($_SESSION['loginbycall_bind_account']);
                $t .= '<div class="loginbycall-message">' . __('Connected successfully', 'loginbycall') . '</div>';
            }
            $t .= '<form action="" method="post" id="loginbycall-user-edit-form" accept-charset="UTF-8">
					<div>
						<input type="hidden" name="user_uid" value="1">
						<div class="form-item form-type-textfield form-item-set-name">
							<label for="edit-set-name">' . __('User name', 'loginbycall') . '&nbsp;&nbsp;&nbsp;-</label>
							<label type="text" id="edit-set-name" name="set_name" size="60" maxlength="128" class="form-text required">' . $i_user->login . '</label>
						</div>
						<div class="form-item form-type-textfield form-item-set-name">
							<label for="edit-set-name">' . __('User mail', 'loginbycall') . '&nbsp;&nbsp;&nbsp;-</label>
							<label type="text" id="edit-set-name" name="set_name" size="60" maxlength="128" class="form-text required">' . $user_email . '</label>
						</div>
						';
            if (get_option('loginbycall_resolution')) { // Option "allow users to control the input and destroy account of service LoginByCall" is on
                $t .= '<div class="form-item form-type-checkbox form-item-user-unbind">
						<input type="checkbox" id="edit-user-unbind" name="user_unbind" value="1" class="form-checkbox">
						<label class="option" for="edit-user-unbind">' . __('Unbind', 'loginbycall') . '</label>
						<div class="description">' . __('Unbind account loginbycall', 'loginbycall') . '</div>
					</div>';
            }
            $t .= '<input type="hidden" name="form_id" value="loginbycall_user_edit_form">
				<input type = "submit" id = "edit-submit" name = "loginbycall-user-edit-form" value = "Submit" class = "form-submit">
					</div>
				</form>';
            $t .= '</br><a href="https://loginbycall.com/users/home#/dashboard/settings" target="_blank">' . __('My settings LoginByCall', 'loginbycall') . '</a>';
        } else { //если пользователя нет в таблице то показать ему ссобшение с ссылкой на создание связи

            $ud = get_userdata($user_ID);

            echo '
		<script>
		function openWindow() {
if (window.myWin !== undefined) { window.myWin.focus(); }
		var leftvar = (screen.width-600)/2;
		var topvar = (screen.height-460)/2;
		myWin = window.open("' . loginbycall_create_link($ud->user_login) . '", "displayWindow", "width=600,height=460,left="+leftvar+",top="+topvar+",status=no,toolbar=no,menubar=no");
		}
</script>';

            $t = __('This site provides users through the service entrance LoginByCall. At this point, your account is not tied to the service. To add a convenient entrance to the site through the service LoginByCall follow the link below:', 'loginbycall')
                . '<br/><a href="#" onclick="openWindow(); return false;" >' . __('Bind your account', 'loginbycall') . '</a>';
        }
        return $t;
    } else {
        return __('You are not allowed to access this page.', 'loginbycall');
    }
}

add_shortcode('loginbycall_settings', 'function_loginbycall_settings_user');


add_filter('page_template', 'loginbycall_redirect_uri_template');

function loginbycall_redirect_uri_template($page_template)
{

    if (is_page('loginbycall-redirect-uri')) {
        $page_template = dirname(__FILE__) . '/template/page-loginbycall-redirect-uri.php';
    }

    return $page_template;
}

function cp_admin_init()
{
    if (!session_id()) {
        session_start();
    }
}

function loginbycall_login_panel_step1()//подключение если нету
{
    $allow = false;
    if (isset($_SESSION['loginbycall_user_login_id'])) {
        $fuser = get_user_by('ID', $_SESSION['loginbycall_user_login_id']);
        if ($fuser) {
            if (get_user_meta($_SESSION['loginbycall_user_login_id'], 'loginbycall_user_phone', true) == '')
                $allow = true;

        }
    }

    if ($allow) {
        echo '<style>#login p label{display: none;}
    p.submit{text-align: center; margin-top: 5px!important;}
                            p.submit input{float: none!important;}
</style>
<p style="text-align: center;">
Подключите простой способ входа
«LoginByСall»для вашего аккаунта
</p>
		<label class="label_inputMsisdn" for="user_phone">Телефон<br>
		<span><span>+</span>
		<input data-require="1" data-filled="false" id="inputMsisdn" class="form-control input-lg phone-number text-center" type="tel" name="loginbycall_phone" placeholder="___________" value="" maxlength="15">
		</span>
		</label>
        <input type="hidden" name="step1_form" value="1">
	    <div class="block-description">Мы позвоним вам со случайного номера,
запомните последние 4 цифры из него.</div>
<p style="margin: 5px 0;">Выберите способ авторизации:</p>';
        $user = get_user_by('ID', $_SESSION['loginbycall_user_login_id']);
        loginbycall_render_login_types($user);
        ?>
        <div style="position: absolute; bottom:-48px;">
        <p style="text-align: center; margin: 0 -18px;">Вы всегда можете изменить настройки в Профиле</p>
        <div style="text-align: center;"><label for="loginbycall_user_refuse">
                <input type="checkbox" name="loginbycall_user_refuse" id="loginbycall_user_refuse" value="1">
                Больше не предлагать</label></div>
        </div>
        <script>
            var flashError = '<?php echo getFlashError()?>';
        </script>

    <?php

    }
}

function loginbycall_login_head_panel_step2()
{
}

//тут обращение к апи по номеру телефона и делаем прозвон
function loginbycall_login_panel_step2()
{

    if (isset($_SESSION['loginbycall_user_login_id']))
        $fuser = get_user_by('ID', $_SESSION['loginbycall_user_login_id']);
    else
        $fuser = false;
    if ($fuser) {
        $allow = loginbycall_check_allowed_role($fuser->roles);
        if (in_array(true, $allow)) {
            $phone = get_user_meta($_SESSION['loginbycall_user_login_id'], 'loginbycall_user_phone', true);
            //звонить только если время вышло, если ввели неправильно звонить не надо, но маску выводить
            //если лимиты вышли позвонить, чтобы попасть на главную с ошибкой


            $phoneCall = call_loginbycall($phone);
            if (lbc_get_safe($phoneCall, 'reason') != '') {
                if (lbc_get_safe($phoneCall, 'error') == 'CALL_REPEAT_TIMEOUT') {
                    $countdown = ceil($phoneCall->additional->delay);
                    $_SESSION['loginbycall_error'] = 'Повторите звонок через ' . $countdown . ' секунд';
                } else {
                    $_SESSION['loginbycall_error'] = lbc_get_safe($phoneCall, 'reason');
                    wp_safe_redirect('wp-login.php');
                    die();
                }

            } else//все ок звонок пошел
            {
                $_SESSION['call'] = lbc_get_safe($phoneCall, 'call');
                $_SESSION['loginbycall_count_login'] = 0;
                $_SESSION['loginbycall_mask_check'] = substr(lbc_get_safe($phoneCall, 'mask'), -lbc_get_safe($phoneCall, 'codelen'));
                $_SESSION['loginbycall_phone_mask'] = substr(lbc_get_safe($phoneCall, 'mask'), 0, strlen(lbc_get_safe($phoneCall, 'mask')) - lbc_get_safe($phoneCall, 'codelen'));
                $countdown = lbc_get_safe($phoneCall, 'repeat_timeout');
            }
            ?>
            <p style="text-align: center; padding: 5px; font-size: 15px;">Ваш номер: +<?php echo $phone ?></p>
            <div class="pin_container"
                 style="background-image:url('<?php echo plugin_dir_url(__FILE__) ?>/img/phone.svg');">
                <div>
                    <div
                        class="phone_mask"><?php echo '+' . (isset($_SESSION['loginbycall_phone_mask']) ? $_SESSION['loginbycall_phone_mask'] : '') ?></div>
                    <input type="phone" name="loginbycall_call_maskphone" id="user_mask" class="input" value="" size="4"
                           maxlength="4" style="width:initial;">
                </div>
            </div>

            <div class="block-description">
                Введите последние 4 цифры
                номера с которого мы вам звоним
            </div>
            <div id="call_status"></div>
            <div id="countdowntext">Повторить через  <?php echo $countdown ?> секунд</div>
            <style>
                #login p label {
                    display: none;
                }

                #countdowntext {
                    text-align: center;
                }

                p.submit {
                    text-align: center;
                    margin-top: 5px !important;
                }

                p.submit input {
                    float: none !important;
                }
            </style>
            <script>
                var _countDown = <?php echo $countdown ?>;
                var flashError = '<?php echo getFlashError()?>';
            </script>
        <?
        }
    }

}

function loginbycall_form_panel()//при регистрации
{

    echo '<p>
            <label class="label_inputMsisdn"  for="user_phone">Телефон<br>';
    echo '<span><span>+</span><input data-require="' . get_option('loginbycall_register_phone') . '" data-filled="false" id="inputMsisdn" class="form-control input-lg phone-number text-center" type="tel" name="loginbycall_user_phone" placeholder="___________" value="" maxlength="15"></span>';
    echo '</label>
    </p>';
    $user = new WP_User();
    $user->roles = array('subscriber');
    loginbycall_render_login_types($user);
    echo '<style>
#reg_passmail{
    display: none;
}</style>
<p>Вы можете изменить настройки в профиле</p>';
}

function loginbycall_uninstall_hook()
{
    delete_option('loginbycall_api_id');
    delete_option('loginbycall_api_key');
    delete_option('loginbycall_credential_active');
    delete_option('loginbycall_new_api_id');
    delete_option('loginbycall_notification_email');
    delete_option('loginbycall_register_phone');
    loginbycall_update_roles(array('_onefactor', '_twofactor'));
}

register_deactivation_hook(__FILE__, 'loginbycall_uninstall_hook');

if (isset($_REQUEST['loginbycall_step']) && $_REQUEST['loginbycall_step'] == 1)
    add_action('login_form', 'loginbycall_login_panel_step1');
elseif (isset($_REQUEST['loginbycall_step']) && $_REQUEST['loginbycall_step'] == 2) {

    add_action('login_head', 'loginbycall_login_head_panel_step2');
    add_action('login_form', 'loginbycall_login_panel_step2');
} else
    add_action('login_form', 'default_login_form');

function default_login_form()
{
    echo "<script>
            var flashError='" . getFlashError() . "';
                </script>";
}

add_action('register_form', 'loginbycall_form_panel');


add_action('init', 'cp_admin_init');
add_action('init', 'loginbycall_run');
add_action('user_register', 'loginbycall_registration_save', 10, 1);


//вызов логина урл для проверки
add_action('wp_ajax_nopriv_verify_logincall', 'verify_logincall');
add_action('wp_ajax_verify_logincall', 'verify_logincall');
function verify_logincall()
{

    echo get_option('loginbycall_api_id');
    die();

}


add_action('wp_ajax_nopriv_call_status_ajax', 'call_status_ajax');
add_action('wp_ajax_call_status_ajax', 'call_status_ajax');
function call_status_ajax()
{
    header('Content-Type: application/json');
    if (isset($_SESSION['call']))
        $status = call_status($_SESSION['call']);
    $status_id = lbc_get_safe($status, 'status');
    if ($status_id == 32)
        $str = lbc_get_safe($status, 'last_error');
    elseif ($status_id >= 4)
        $str = 'Звонок завершен';
    else
        $str = 'Звонок совершается';
    echo json_encode(array('id' => $status_id, 'textMsg' => $str));
    die();
}

add_action('wp_ajax_nopriv_verify_logincall_pin', 'verify_logincall_pin');

function verify_logincall_pin()
{

    header('Content-Type: application/json');
    $data = array('redirect' => 0);
    if (isset($_SESSION['loginbycall_user_login_id']) && isset($_POST['loginbycall_call_maskphone'])) {

        $_SESSION['loginbycall_count_login']++;
        if ($_SESSION['loginbycall_count_login'] > 3) {
            $_SESSION['loginbycall_mask_check'] = null;
            $data['error'] = __('<strong>ERROR</strong>: Maximum number of retries exceeded.');
            echo json_encode($data);
            die();
        }

        if ($_SESSION['loginbycall_mask_check'] == $_POST['loginbycall_call_maskphone']) {
            $_SESSION['loginbycall_count_login'] = 0;
            wp_set_auth_cookie($_SESSION['loginbycall_user_login_id']);
            if (get_user_meta($_SESSION['loginbycall_user_login_id'], 'loginbycall_user_active', true) != 1)
                update_user_meta($_SESSION['loginbycall_user_login_id'], 'loginbycall_user_active', 1);
            unset($_SESSION['loginbycall_user_login_id']);
            $data = array('redirect' => 1);
        } else {
            $data['error'] = __('<strong>ERROR</strong>: Phone not accepted.');

        }
    }
    echo json_encode($data);
    die();
}


add_action('wp_ajax_loginbycall_get_balance', 'loginbycall_get_balance');
function loginbycall_get_balance()
{
    $balance = balance_loginbycall();
    echo(lbc_get_safe($balance, 'balance') - lbc_get_safe($balance, 'consumed'));
    die();
}


add_filter('check_password', 'loginbycall_check_password', 10, 4);
add_filter('authenticate', 'loginbycall_auth_signon', 10, 3);

function loginbycall_auth_signon($user, $username, $password)
{
    //сама авторизация по протоколу
    if (isset($_SESSION['loginbycall_user_login_id']) && isset($_POST['loginbycall_call_maskphone'])) {

        $_SESSION['loginbycall_count_login']++;
        if ($_SESSION['loginbycall_count_login'] > 3) {
            $_SESSION['loginbycall_mask_check'] = null;

            $_SESSION['loginbycall_error'] = __('<strong>ERROR</strong>: Maximum number of retries exceeded.');
            wp_safe_redirect('wp-login.php?loginbycall_step=2');
            die();
        }

        if ($_SESSION['loginbycall_mask_check'] == $_POST['loginbycall_call_maskphone']) {
            $_SESSION['loginbycall_count_login'] = 0;
            wp_set_auth_cookie($_SESSION['loginbycall_user_login_id']);
            if (get_user_meta($_SESSION['loginbycall_user_login_id'], 'loginbycall_user_active', true) != 1)
                update_user_meta($_SESSION['loginbycall_user_login_id'], 'loginbycall_user_active', 1);
            unset($_SESSION['loginbycall_user_login_id']);
            wp_safe_redirect('/wp-admin/');
        } else {
            $_SESSION['loginbycall_error'] = __('<strong>ERROR</strong>: Pin not accepted.');
            wp_safe_redirect('wp-login.php?loginbycall_step=2');
        }
        die();
    }

    //если уже раз прошли форму авторизации
    if (isset($_SESSION['loginbycall_user_login_id']) && is_numeric($_SESSION['loginbycall_user_login_id'])) {
        $user_id = $_SESSION['loginbycall_user_login_id'];
    }

    //попадаем сюда с формы для уже зареганых юзеров без телефона
    if (isset($_REQUEST['step1_form']) && $_REQUEST['step1_form'] == 1 &&isset($user_id)) {
        //если отказался то мы логиним по обычному
        if (isset($_REQUEST['loginbycall_user_refuse']) && $_REQUEST['loginbycall_user_refuse'] == 1) {
            update_user_meta($user_id, 'loginbycall_user_refuse', $_REQUEST['loginbycall_user_refuse']);
            unset($_SESSION['loginbycall_user_login_id']);
            wp_set_auth_cookie($user_id);
            wp_safe_redirect('/wp-admin/');
            die();
        } else {
            $fuser = get_user_by('id', $user_id);
            if (isset($_POST['loginbycall_phone'])) {

            }
            if (!loginbycall_is_unique_phone($_POST['loginbycall_phone'])) {
                $_SESSION['loginbycall_error'] = __('<strong>ОШИБКА</strong>: Телефон уже занят.');
                wp_safe_redirect('wp-login.php?loginbycall_step=1');
                die();
            }
            $allow = loginbycall_check_allowed_role($fuser->roles);
            if (in_array(true, $allow)) {
                update_user_meta($user_id, 'loginbycall_user_login_type', $_POST['loginbycall_user_login_type']);
                update_user_meta($user_id, 'loginbycall_user_phone', $_POST['loginbycall_phone']);
                wp_safe_redirect('wp-login.php?loginbycall_step=2');
                die();
            }
        }
    }

    if (!empty($username))//если сессия пуста то нам надо понять стоить давать ошибку или пропускать юзера дальше
    {
        if (is_email($username))
            $find = 'email';
        else
            $find = 'login';
        $fuser = get_user_by($find, $username);

        if ($fuser)//если юзер найден и логин только по нику то пускаем дальше
        {
            $refuse = get_user_meta($fuser->ID, 'loginbycall_user_refuse', true);
            $allow = loginbycall_check_allowed_role($fuser->roles);
            //если однофакторный, есть доступ, не отказался,статус сервера ок то идем то авторизация по моб
            if (get_user_meta($fuser->ID, 'loginbycall_user_login_type', true) == 1 && $allow['_onefactor'] && $refuse != 1  && server_status() == 1) {
                //если телефон пуст то надо кинуть ему предложение
                $_SESSION['loginbycall_user_login_id'] = $fuser->ID;
                if(!is_numeric(get_user_meta($fuser->ID, 'loginbycall_user_phone', true)))
                    wp_safe_redirect('wp-login.php?loginbycall_step=1');
                else
                    wp_safe_redirect('wp-login.php?loginbycall_step=2');
                die();

            }
        }//идем проверять пароль
    }

    return $user;
}
//здесь проверям однуфакторную или двухфакторную авторизацию
//сюда пустой пароль не доходит
function loginbycall_check_password($check, $password, $hash, $user_id)
{

    if (get_user_meta($user_id, 'loginbycall_user_refuse', true) == 1)//если юзер отказался то обычная проверки
        return $check;

    //пароль прошел и хватает прав лезть дальше

    if ($check && server_status() == 1) {
        $user = get_user_by('ID', $user_id);
        $allow = loginbycall_check_allowed_role($user->roles);
        if (in_array(true, $allow)) {
            $_SESSION['loginbycall_user_login_id'] = $user_id;
            //если телефон не забит, то надо предложить его забить

            if (!is_numeric(get_user_meta($user_id, 'loginbycall_user_phone', true))) {
                wp_safe_redirect('wp-login.php?loginbycall_step=1');
            } else
                wp_safe_redirect('wp-login.php?loginbycall_step=2');
            die();
        } else {
            return $check;

        }
    } else
        return $check;
}

function loginbycall_phone_check($errors, $sanitized_user_login, $user_email)
{

    if (get_option('loginbycall_register_phone') == 1 && $_POST['loginbycall_user_phone'] == '') {
        $errors->add('zipcode_error', __('<strong>ОШИБКА</strong>: Телефон должен быть заполнен.', 'loginbycall'));
    } elseif (strlen($_POST['loginbycall_user_phone']) > 0 && !loginbycall_is_unique_phone($_POST['loginbycall_user_phone'])) {
        $errors->add('zipcode_error', __('<strong>ОШИБКА</strong>: Телефон уже занят.', 'loginbycall'));
    }
    return $errors;
}

add_filter('registration_errors', 'loginbycall_phone_check', 10, 3);


function loginbycall_registration_save($user_id)
{
    if (isset($_POST['loginbycall_user_phone'])) {
        update_user_meta($user_id, 'loginbycall_user_phone', $_POST['loginbycall_user_phone']);
    }
    $_SESSION['loginbycall_user_login_id'] = $user_id;
    update_user_meta($user_id, 'loginbycall_user_active', 0);
    wp_schedule_single_event(time() + 60 * 60 * 24, 'loginbycall_delete_users', array($user_id));
    if (isset($_POST['loginbycall_user_login_type']))
        update_user_meta($user_id, 'loginbycall_user_login_type', $_POST['loginbycall_user_login_type']);
    if (server_status() == 1) {
        if (get_option('loginbycall_subscriber_onefactor') == 1) {
            wp_safe_redirect('wp-login.php?loginbycall_step=2');
            die();
        }
    }


}

add_action('loginbycall_delete_users', 'loginbycall_delete_users_daily', 10, 1);
function loginbycall_delete_users_daily($user_id)
{

    if (get_user_meta($user_id, 'loginbycall_user_active', true) != 1 && get_user_meta($user_id, 'loginbycall_user_phone', true) != '') {
        require_once(ABSPATH . 'wp-admin/includes/user.php');

        wp_delete_user($user_id);

    }

}

register_activation_hook(__FILE__, 'loginbycall_install');

?>
