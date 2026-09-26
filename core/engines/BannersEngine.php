<?php

namespace AC\core\engines;

use AC\core\system\db\Query;

class BannersEngine
{
  protected $cache_exists;

  public $image_path;

  public function __construct()
  {
    $this->cache_exists = array();
    $this->image_path = pathAs(paths()->getAssetsDir('images/bilder/', 'common'));
  }

  function Initialize()
  {
  }

  function Finalize()
  {
  }


  /* ИЗМЕНЕНИЯ */

  function insertBanner($alt, $url, $sort, $publish, $image, &$new_banner_id)
  {
    $q      = 'insert into ' . Query::tableName('banners') . '
			set
				alt = :alt,
				url = :url,
				sort = :sort,
				publish = :publish';
    $params = [
      'alt'     => $alt,
      'url'     => $url,
      'sort'    => $sort,
      'publish' => $publish
    ];
    Query::sqlQuery($q, $params, false);

    $new_banner_id = Query::getLastId();
    if ($image !== null) {
      copy($image, ROOT_PATH . $this->image_path . $new_banner_id . '.gif');
    }
    $this->cache_exists[$new_banner_id] = true;

    return true;
  }

  function changeBanner($banner_id, $alt, $url, $sort, $publish, $image)
  {
    if ($this->checkBannerExists($banner_id)) {
      $q = 'update ' . Query::tableName('banners') . '
				set
					alt = :alt,
					url = :url,
					sort = :sort,
					publish = :publish 
				where banner_id = :banner_id';

      $params = [
        'alt'       => $alt,
        'url'       => $url,
        'sort'      => $sort,
        'publish'   => $publish,
        'banner_id' => $banner_id
      ];

      Query::sqlQuery($q, $params, false);

      if ($image !== null) {
        copy($image, ROOT_PATH . $this->image_path . $banner_id . '.gif');
      }

      return true;
    } else {
      return false;
    }
  }

  function removeBanner($banner_id)
  {
    $tmp = Query::sqlQuery('delete from ' . Query::tableName('banners') . ' where banner_id = ' . $banner_id);
    if ($tmp) {
      //удаляем картинки
      $p = ROOT_PATH . $this->image_path . $banner_id . '.gif';
      if (is_file($p)) {
        unlink($p);
      }

      return true;
    } else {
      return false;
    }
  }


  /* ВЫДАЧА */

  function getBanner($banner_id, &$result)
  {
    $temp = Query::sqlQuery('select * from ' . Query::tableName('banners') . ' where banner_id = ' . $banner_id);
    if (!empty($temp)) {
      $this->cache_exists[$banner_id] = true;
      $result                         = $temp[0];

      $p               = $this->image_path . $banner_id . '.gif';
      $result['image'] = is_file(ROOT_PATH . $p) ? $p : null;

      return true;
    } else {
      $this->cache_exists[$banner_id] = false;
      $result                         = null;

      return false;
    }
  }

  public function getBanners(&$result, $limit, $publish)
  {
    $temp = Query::sqlQuery(
      'select * from ' . Query::tableName('banners') . '
			' . (!is_null($publish) ? ' where publish = ' . $publish : '') . '
			order by sort asc' . ($limit !== null ? ' limit ' . $limit : '')
    );
    if (!empty($temp)) {
      $result = array();
      foreach ($temp as $row) {
        $this->cache_exists[$row['banner_id']] = true;

        $p            = $this->image_path . $row['banner_id'] . '.gif';
        $row['image'] = is_file(ROOT_PATH . $p) ? $p : null;

        $result[] = $row;
      }

      return true;
    } else {
      return false;
    }
  }

  //private
  function checkBannerExists($banner_id)
  {
    //кэш
    if (isset ($this->cache_exists[$banner_id])) {
      return $this->cache_exists[$banner_id];
    }

    //пробиваем в БД
    $temp                           = Query::sqlQuery(
      'select count(*) as cnt from ' . Query::tableName('banners') . ' where banner_id = ' . $banner_id
    );
    $row                            = $temp[0];
    $this->cache_exists[$banner_id] = $row['cnt'] > 0;

    return $this->cache_exists[$banner_id];
  }

}

?>