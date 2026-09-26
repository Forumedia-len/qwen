<?php
//2004-10-20
//вариант класса utils специально для проекта tenishalle-villingen

class utils
{
  function Initialize()
  {
  }

  function Finalize()
  {
  }

  function convertDateEuro2Mysql($rus_date)
  {
    $rus_date   = explode('.', $rus_date);
    $mysql_date = $rus_date[2] . '.' . $rus_date[1] . '.' . $rus_date[0];

    return $mysql_date;
  }


}

?>