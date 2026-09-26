<?php
/**
 * Created by PhpStorm.
 * User: dev_1
 * Date: 26.04.2019
 * Time: 14:35
 */

if($_POST["action"] == "db_rename")
{    
    define ('DB_HOST', $_POST['DB_HOST']);
    define ('DB_USERNAME', $_POST['DB_USERNAME']);
    define ('DB_PASSWORD', $_POST['DB_PASSWORD']);
    define ('DB_DATABASE_NAME', $_POST['DB_DATABASE_NAME']);

    define ('OLD_DB_TABLE_PREFIX', $_POST['OLD_DB_TABLE_PREFIX']);
    define ('NEW_DB_TABLE_PREFIX', $_POST['NEW_DB_TABLE_PREFIX']);

    $tableNames = array("accounts",
                        "areas_sports",
                        "accounts_clients",
                        "accounts_confirmation_delete",
                        "accounts_reservations",
                        "accounts_reservations_light_tickets",
                        "accounts_reservations_others",
                        "accounts_reservations_prepayment",
                        "accounts_reservations_tickets",
                        "accounts_text_config",
                        "areas",
                        "areas_lights",
                        "areas_prices",
                        "areas_prices_periods",
                        "areas_timetables",
                        "areas_types",
                        "banners",
                        "blocks",
                        "blocks_periodical",
                        "coupon",
                        "coupon_code",
                        "clients",
                        "config",
                        "config_discount",
                        "config_extra",
                        "config_nds",
                        "config_pp",
                        "config_text",
                        "club_reservation_rules",
                        "doorcodes",
                        "holidays",
                        "letters_templates",
                        "light_logs",
                        "news_simplest",
                        "reservations",
                        "reservations_specprice",
                        "reservations_stocks",
                        "reservations_tmp_paypal",
                        "tickets",
                        "tickets_light",
                        "tickets_periods",
                        "users");


    if(!mysql_connect(DB_HOST,DB_USERNAME,DB_PASSWORD))
    {
        mysql_error();
        die;
    }

    if(!mysql_select_db(DB_DATABASE_NAME))
    {
        mysql_error();
        die;
    }

    foreach($tableNames as $tableName)
    {
        $renameQuery = "RENAME TABLE ". DB_DATABASE_NAME .".". OLD_DB_TABLE_PREFIX . $tableName ." TO ". DB_DATABASE_NAME .".". NEW_DB_TABLE_PREFIX . $tableName;
        
        if(mysql_query($renameQuery))
        {
            echo "<p style='color:green;'>TABLE ".$tableName . " RENAMED</p>";
        }
        else
        {
            echo "<p style='color:red;'>TABLE ".$tableName . " NOT RENAMED</p>";
        }
    }
}
else
{
?>
    <form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
    <input type= "hidden" name= "action"                 value= "db_rename">
    <label for="DB_HOST">DB_HOST</label><br/>
    <input type= "text"   name= "DB_HOST"             id= "DB_HOST"     value= "localhost" required><br/>
    <label for="DB_USERNAME">DB_USERNAME</label><br/>
    <input type= "text"   name= "DB_USERNAME"         id= "DB_USERNAME" value= "mysql" required><br/>
    <label for="DB_PASSWORD">DB_PASSWORD</label><br/>
    <input type= "text"   name= "DB_PASSWORD"         id= "DB_PASSWORD" value= "mysql" required><br/>
    <label for="DB_DATABASE_NAME">DB_DATABASE_NAME</label><br/>
    <input type= "text"   name= "DB_DATABASE_NAME"    id= "DB_DATABASE_NAME" required><br/>
    <label for="OLD_DB_TABLE_PREFIX">OLD_DB_TABLE_PREFIX</label><br/>
    <input type= "text"   name= "OLD_DB_TABLE_PREFIX" id= "OLD_DB_TABLE_PREFIX" placeholder= "at_muenster_" required><br/>
    <label for="NEW_DB_TABLE_PREFIX">NEW_DB_TABLE_PREFIX</label><br/>
    <input type= "text"   name= "NEW_DB_TABLE_PREFIX" id= "NEW_DB_TABLE_PREFIX" placeholder= "at_simmern_" required><br/>
    <br/>
    <input type= "submit" value= "rename">
    </form>
<?php
}


