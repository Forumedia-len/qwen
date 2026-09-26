<?php

namespace AC\core\modules\reservations\models;

use AC\core\system\helpers\ConfigHelper;
use AC\core\system\exceptions\http\RequestValidationException;
use AC\core\modules\reservations\exceptions\ReservationDateUnavailableException;
use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\areas\models\AreasSportsModel;
use AC\core\modules\areas\models\AreasTypesModel;
use AC\core\modules\clients\models\ClientsModel;
use AC\core\modules\discounts\models\DiscountsModel;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\modules\payment\helpers\PaymentHelper;
use AC\core\modules\reservations\config\ReservationsConfig;
use AC\core\modules\reservations\entities\dto\ReservationClientDto;
use AC\core\modules\reservations\entities\dto\ReservationSpecPriceDto;
use AC\core\modules\reservations\entities\dto\ReservationStockDto;
use AC\core\modules\reservations\locators\ReservServiceLocator;
use AC\core\modules\reservations\tables\ReservationsTable;
use AC\core\modules\webio\models\WebIoModel;
use AC\core\modules\webio\models\WebIoTypeSatesModel;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\model\InitializableModelInterface;
use Service;

/**
 * Class ReservationsModel
 *
 * Модель для работы с бронированиями.
 *
 * @todo вынести все обращение не наследованием этой модельки а созданием свойства reservation
 */
class ReservationsModel extends ReservationsTable implements InitializableModelInterface
{
  /**
   * @var string Название функции получения данных по ID бронирования
   */
  public $baseGetFunction = 'getReservationDataById';

  /**
   * @var string Первичный ключ модели
   */
  public $primary_key = 'reservation_id';

  /**
   * @var string Имя базового движка
   */
  public $baseEngine = 'Engines';

  /**
   * @var bool Использовать специальную цену и опции для левого игрока
   */
  public $useBarClient = USE_OPTION_AND_SPEC_PRICE_FOR_LEFT_PLAYER;

  /**
   * @var mixed Время начала
   */
  public $time_start;

  /**
   * @var mixed Время окончания
   */
  public $time_finish;

  /**
   * @var array Массив сумм цены
   */
  public $sum_price_array;

  /**
   * @var bool Флаг PayPal
   */
  public $pay_pal_true;

  /**
   * @var bool Левый клиент
   */
  public $barClient = false;

  /**
   * @var bool Запрос пришел из админки
   */
  public $admin = false;

  /**
   * @var bool Запрос пришел от тачскрина
   */
  public $touch = false;

  /**
   * @var bool Открытые корты переключатель
   */
  public $open = false;

  /**
   * @var int Тип площадки
   */
  public $type_id;

  /**
   * @var int Спорт
   */
  public $sport_id;

  /**
   * @var bool Неделя
   */
  public $week = false;

  /**
   * @var int Номер страницы
   */
  public $page;

  /**
   * @var array Ограничение вывода записей
   */
  public $_limit;

  /**
   * @var array Временные промежутки
   */
  public $times;

  /**
   * @var float Цена за свет
   */
  public $state_sum_price = [];

  /**
   * @var ClientsModel Текущий клиент
   */
  public $current_client;

  /**
   * @var AreasModel Площадка
   */
  public $areas;

  /**
   * @var AreasTypesModel Типы площадок
   */
  public $areas_types;

  /**
   * @var AreasSportsModel Спортивные площадки
   */
  public $areas_sports;

  /**
   * @var array Игроки по времени
   */
  public $playersByTime = [];
  /**
   * @var string Код админа
   */
  public string $customerTitle = '';

  /**
   * @var bool Использовать базовый движок
   */
  protected $useBaseEngine = true;

  /**
   * @var array Коды дверей
   */
  public $door_codes = [];

  /**
   * @var array Заголовки временных промежутков
   */
  protected $timeTitles = [];

  /**
   * @var string Текущий заголовок временного промежутка
   */
  protected $timeTitle = '';

  /**
   * @var array Добавить ставку акции
   */
  protected array $addStockRate = [];

  private $initialReservationId;
  private bool $initialTmp;
  private bool $initialSecond;
  private bool $initialized = false;

  /**
   * ReservationsModel constructor.
   *
   * Сохраняет параметры загрузки; данные загружаются методом initialize() после установки admin/touch.
   *
   * @param int|string|null $id ID бронирования
   * @param bool $tmp    Флаг временной модели
   * @param bool $second Флаг второй модели
   */
  public function __construct($id = null, $tmp = false, $second = false)
  {
    parent::__construct(null);
    $this->initialReservationId = $id;
    $this->initialTmp = (bool)$tmp;
    $this->initialSecond = (bool)$second;
  }

  /** Загрузить данные один раз после установки контекста устройства. */
  final public function initialize(): void
  {
    if ($this->initialized) {
      return;
    }
    $this->initializeData();
    $this->initialized = true;
  }

  /** Загрузить бронирование или параметры запроса с учётом контекста устройства. */
  protected function initializeData(): void
  {
    $id = $this->initialReservationId;
    $tmp = $this->initialTmp;
    $second = $this->initialSecond;
    if ($id !== null) {
      $this->setReservationById($id, $tmp, $second);
    } else {
      // Площадка задаёт значения по умолчанию до выбора спорта и проверки горизонта бронирования.
      $this->getAreaId();
      $this->areas = new AreasModel($this->area_id);
      $this->getTypeId();
      $this->getSportId();
      $this->getPage();
      $this->getDate(true);
      $this->getWeek();
      $client_id = Service::request()->_('client_id');
      if (!$client_id) {
        $client_id = 'current';
      }
      $this->current_client = new ClientsModel($client_id);
      $this->client_id      = (int)$this->current_client->client_id;
    }

    $this->areas ??= new AreasModel($this->area_id);
    if ((empty($this->type_id) || empty($this->sport_id)) && !empty($this->area_id)) {
      $this->type_id  = $this->areas->type_id;
      $this->sport_id = $this->areas->sport_id;
    }

    if ($this->client_id == null && !$second) {
      $this->checkBarClient();
    }

    $this->areas_types  = new AreasTypesModel($this->type_id);
    $this->areas_sports = new AreasSportsModel($this->sport_id);
    if ($id !== null && $this->areas_types->alias == 'open') {
      $this->open = true;
    }
    $this->getTimes();
    $this->setTimeTitles();
    $this->setPlayersByTime();
    $this->setDefaultStateSumPrice();
    $this->engine->setMaxReservationUnixTime($this->type_id, $this->sport_id);
  }

  /**
   * prefixTitle
   *
   * Возвращает префикс заголовка.
   *
   * @return string
   */
  public function prefixTitle()
  {
    return '';
  }

  /**
   * checkBarClient
   *
   * Проверяет, не зарегирован ли левый клиент.
   *
   * @return bool
   */
  public function checkBarClient()
  {
    if ($this->barClient) {
      return true;
    }

    if (Service::auth()->isBarClient()) {
      $this->pay_pal_true               = isset($_SESSION['paypal_true']) && $_SESSION['paypal_true'] == true;
      $this->client_id                  = config('reservations')->isOpenType((int)$this->type_id) ? 'guest' : null;
      $this->current_client->client_id  = null;
      $this->current_client->name       = $_SESSION['name'];
      $this->client_name                = $_SESSION['name'];
      $this->current_client->surname    = $_SESSION['surname'];
      $this->client_surname             = $_SESSION['surname'];
      $this->current_client->email      = $_SESSION['email'];
      $this->current_client->area_type  = 0;
      $this->current_client->club_state = 1;
      $this->current_client->encash     = 3;
      $this->barClient                  = true;

      return true;
    }

    return false;
  }

  /**
   * getTimes
   *
   * Получает временные промежутки из запроса.
   *
   * @return array
   */
  public function getTimes()
  {
    $time = Service::request()->validated('time', 'request', [['time', ['allowArray' => true]]], $this->time ?: null);

    $this->times = [];
    if ($time !== null) {
      if (is_array($time)) {
        foreach ($time as $_time => $value) {
          if ((int)$value == 1) {
            $this->times[] = $_time;
          }
        }
        if ($this->times === []) {
          return [];
        }
        $this->time = $this->times[0];
      } else {
        $this->times[] = $time;
        $this->time    = $time;
      }
      $this->time_start  = $this->time;
      $this->time_finish = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($this->time) + $this->areas->period);

      if ($this->open && $this->areas->period == '15') {
        $time2             = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($this->time) + $this->areas->period);
        $this->times[]     = $time2;
        $time3             = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time2) + $this->areas->period);
        $this->times[]     = $time3;
        $time4             = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time3) + $this->areas->period);
        $this->times[]     = $time4;
        $this->time_finish = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time4) + $this->areas->period);
      }
      if ($this->open && $this->areas->period != '15') {
        $time2             = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($this->time) + $this->areas->period);
        $this->times[]     = $time2;
        $this->time_finish = TimeHelper::convertMinutes2MySQLTime(TimeHelper::convertMySQLTimeToMinutes($time2) + $this->areas->period);
      }
    }

    return $this->times;
  }

  /**
   * getSportId
   *
   * Получает ID спорта из запроса.
   *
   * @return int
   */
  public function getSportId()
  {
    $defaultSportId = !empty($this->areas->sport_id) && (int)$this->areas->type_id === (int)$this->type_id
      ? $this->areas->sport_id
      : $this->engine->areas->getMinSportByType($this->type_id);
    $sportId = Service::request()->validated('sport_id', 'request', [['integer', ['min' => 0]]], $defaultSportId);
    if (!isset($this->sport_id) || $this->sport_id != $sportId) {
      $this->sport_id = (int)$sportId;
    }

    return $this->sport_id;
  }

  /**
   * getAreaId
   *
   * Получает ID площадки из запроса.
   *
   * @return int
   */
  public function getAreaId(): int
  {
    $areaId = Service::request()->validated('area_id', 'request', [['integer', ['min' => 0]]], 0);
    if (empty($this->area_id)
      || (Service::request()->check('area_id') && $this->area_id != $areaId)) {
      $this->area_id = $areaId;
    }

    return $this->area_id;
  }

  /**
   * getWeek
   *
   * Получает номер недели из запроса.
   *
   * @return int
   */
  public function getWeek()
  {
    $week = Service::request()->validated('week', 'request', 'integer', 0);
    if (!isset($this->week) || $this->week != $week) {
      $this->week = $week;
    }

    return $this->week;
  }

  /**
   * getTypeId
   *
   * Получает ID типа площадки из запроса.
   *
   * @return int
   */
  public function getTypeId()
  {
    $type_id = (int)Service::request()->validated('type_id', 'request', [['integer', ['min' => 0]]],
      $this->areas->type_id ?? module('areas')->useModel()->getFirstActiveType());
    if (!isset($this->type_id) || $this->type_id != $type_id) {
      if (isset($_SESSION['type_id'])) {
        $this->type_id = $_SESSION['type_id'];
      } else {
        $this->type_id = $type_id;
      }
    }

    return $this->type_id;
  }

  /**
   * getPage
   *
   * Получает номер страницы из запроса.
   *
   * @return int
   */
  public function getPage()
  {
    $page = Service::request()->validated('page', 'request', 'integer', 1);
    $page = USE_PAGE_BREAK_FROM_BASE_AREA
      ? $this->getPageBaseStructure($page)
      : $this->getPageDefaultStructure($page);

    return $page;
  }

  /**
   * getPageDefaultStructure
   *
   * Возвращает номер страницы для стандартной структуры.
   *
   * @param int $page Номер страницы
   *
   * @return int
   */
  private function getPageDefaultStructure($page)
  {
    $per_page = $this->getPerPage();

    $this->engine->areas->getAreasTitlesByType($this->type_id, $areas);
    $areas_count = count($areas);
    if ($areas_count > $per_page) {
      //навигация нужна
      //определяем номер текущей странички
      if ($page < 1 || $page > ceil($areas_count / $per_page)) {
        $page = 1;
      }
      //переменная для renderAreasTypeColumns
      $this->_limit = [($page - 1) * $per_page, $per_page];

      //Вариант построения кортов на станицу стрит версии открытых кортов
      //По сути, делает так что бы последняя страница стрит версии была без пустых столбцов
      if ($this->open && $this->touch == true) {
        $missing_courts_num = 0;
        //Если нумерация кортов на следующей странице превышает общее количество кортов, то текущая страница является последней
        if (($page * $per_page) > $areas_count && $per_page < $areas_count) {
          $max_on_prev_page   = $page * $per_page;
          $missing_courts_num = $max_on_prev_page - $areas_count;
          if ($missing_courts_num < 0) {
            $missing_courts_num = 0;
          }
        }

        $this->_limit = [(($page - 1) * $per_page) - $missing_courts_num, $per_page];
      }
    } else {
      $page         = 1;
      $this->_limit = null;
    }
    $this->page = $page;

    return $page;
  }

  /**
   * getPageBaseStructure
   *
   * Возвращает номер страницы для базовой структуры.
   *
   * @param int $page Номер страницы
   *
   * @return int
   */
  private function getPageBaseStructure($page)
  {
    $pageData     = $this->engine->areas->resolveAreasPage((int)$this->type_id, (int)$this->sport_id, (int)$page);
    $this->page   = $pageData['page'];
    $this->_limit = $pageData['limit'];

    return $this->page;
  }

  /**
   * getDate
   *
   * Получает выбранную дату из запроса.
   *
   * @param bool $new
   * @throws RequestValidationException При неверном формате даты запроса
   * @throws ReservationDateUnavailableException При дате вне доступного периода бронирования
   *
   * @return string
   */
  public function getDate(bool $new = false): string
  {
    if (empty($this->date) || $new) {
      $date = Service::request()->validated('date', 'request', [['date', ['allowDottedFormat' => true, 'normalize' => true]]],
        date('Y-m-d'));
      // устанавливаем максимальную дату бронирования для данного типа площадки
      $this->engine->setMaxReservationUnixTime($this->type_id, $this->sport_id);
      $unix_time = strtotime($date);
      if (!($this->engine->checkDateAvaliableByUnixtime($unix_time) || $this->isDateInCalendarPeriod($date)) && !$this->admin) {
        // Нельзя создавать заказ на сегодня вместо явно выбранной пользователем даты.
        throw new ReservationDateUnavailableException();
      }
      $this->date = $date;
    }

    return $this->date;
  }

  /** Проверяет дополнительный период отображения календаря для текущих типа площадки и спорта. */
  protected function isDateInCalendarPeriod(string $date): bool
  {
    if (!PERIOD_SHOW_CALENDAR) {
      return false;
    }
    $cntDays = ConfigHelper::parseStringToVariables(PERIOD_SHOW_CALENDAR, 'PERIOD_SHOW_CALENDAR');
    $days = $cntDays["{$this->type_id}_{$this->sport_id}"] ?? null;
    return !empty($days) && ConfigHelper::checkDateInInterval(date('Y-m-d'), $days, $date);
  }

  /**
   * isAjax
   *
   * Проверяет, является ли запрос AJAX.
   *
   * @return bool
   */
  public function isAjax()
  {
    return (bool)Service::request()->_('ajax', 0);
  }

  /**
   * getSumPriceTimePercent
   *
   * Корректировка цены согласно коэффициенту за последовательно идущие игры.
   * Для левых игроков и ничленов. kaltlufthalle-altdorf.de
   *
   * @param string               $time        Время
   * @param ReservationClientDto $client      Клиент
   * @param float &              $price       Цена
   * @param float &              $extra_price Дополнительная цена
   *
   * @return bool
   * @todo почему здесь только для закрытых возможно нужно доработать
   *                                          думаю можно это будет перенести в club_reservation_rules
   */
  public function getSumPriceTimePercent($time, ReservationClientDto $client, &$price, &$extra_price)
  {
    if ($this->areas_types->alias === 'close' && defined('PRICE_NEXT_GAME') && PRICE_NEXT_GAME) {
      if ($this->engine->clients->isBar() || $client->clubState === 1) {
        $pos = array_flip($this->times)[$time];
        if ($pos != 0
          && (strtotime($this->date . ' ' . $this->times[$pos]) - strtotime($this->date . ' ' . $this->times[$pos - 1]) <= $this->areas->period * 60)
          || $this->engine->checkAreaDateTimeOrdered(
            $this->area_id,
            date('Y-m-d H:i:s', strtotime($this->date . ' ' . $time) - $this->areas->period * 60),
            null,
            $client->id
          )) {
          $price       = PRICE_NEXT_GAME * $price;
          $extra_price = PRICE_NEXT_GAME * $extra_price;

          return true;
        }
      }
    }

    return false;
  }

  /**
   * getSumPriceTime
   *
   * Получает сумму стоимостей для выбранного времени.
   *
   * @param bool &       $check_area Возможность забронировать
   * @param bool|string &$error_code Код ошибки
   *
   * @return float
   */
  public function setTotalCostPayment(&$check_area, &$error_code = false): float
  {
    $check_area = true;
    $sum        = 0;
    if (isset($this->times) && is_array($this->times) && count($this->times) > 0) {
      foreach ($this->times as $time) {
        $this->setStartPriceByTime($time);
        $price = $this->checkPriceClientForPeriod($this->playersByTime[$time], $time, $prices, $error_code);
        if (gettype($price) == 'boolean' && $price === false) {
          $check_area = false;
          break;
        }
        $this->sum_price_array[$time]              = $prices;
        $price                                     += $this->getSumPriceForStateLHN($time);
        $sum                                       += $price;
        $this->sum_price_array[$time]['totalCost'] = round($price, 2);
      }
    } else {
      $check_area = false;
    }
    $sum         = round(max($sum, 0), 2);
    $this->price = $sum;

    return NumberHelper::float($sum);
  }

  /**
   * checkPriceClientForPeriod
   *
   * Проверяет и получает полную стоимость игры для выбранного времени и клиента.
   *
   * @param mixed  $client_id              ID клиента
   * @param string $time                   Время
   * @param array &$prices                 Массив цен
   * @param bool  &$error_code             Код ошибки
   * @param bool   $processingPrice        Обработка цены
   * @param bool   $getSumPriceTimePercent Получение процентной корректировки цены
   *
   * @return bool
   */
  public function checkPriceClientForPeriod(
    $client_id,
    $time,
    &$prices = [],
    &$error_code = false,
    $processingPrice = true,
    $getSumPriceTimePercent = true,
  ) {
    $prices = $this->setStartPricesBlock();
    //форма подтверждения заказа
    //проверка на присутствие даты в доступном промежутке
    if (!$this->engine->checkAreaDateTimeAvailable(
      $this->area_id,
      $client_id === 'guest' ? null : $client_id,
      $this->date,
      $time,
      $error_code
    )) {
      return false;
    }
    $client = ReservServiceLocator::client($client_id ?? 'guest');

    if (!$this->engine->pricing->getPeriodPrice(
      $this->area_id,
      $client->id,
      $this->date,
      $time,
      $price,
      $extra_price
    )) {
      return false;
    }
    if ($getSumPriceTimePercent && defined('PRICE_NEXT_GAME') && PRICE_NEXT_GAME) {
      $this->getSumPriceTimePercent($time, $client, $price, $extra_price);
    }

    $prices['price'] = (float)$price;
    $prices['extra'] = (float)$extra_price;
    /** @var ReservationsConfig $configReservation */
    $configReservation = config('Reservations');
    // применять наценки, скидки свет (для открытых не используется), не используется для левого клиента
    $openType = $this->open
      && $configReservation->isOpenCourtForPrices($this->areas_types->alias, $this->type_id, $this->sport_id, $this->area_id);

    if (!$openType) {
      $price += $extra_price;
    }
    if ($processingPrice && $configReservation->useOptionsPrice($openType)) {
      $price = $this->processingPrice($client, $time, $price, $prices);
    }
    $prices['sum_price'] = round(max((float)$price, 0), 2);

    return $price;
  }

  /**
   * processingPrice
   *
   * Обработка цены (наценки, акции, скидки).
   *
   * @param ReservationClientDto $client Клиент
   * @param string               $time   Время
   * @param float                $price  Цена
   * @param array &              $prices Массив цен
   *
   * @return float
   * @todo переработать чтобы все наценки брались из модели клиента
   */
  public function processingPrice(ReservationClientDto $client, $time, $price, &$prices = [])
  {
    static $area;
    if (empty($area)) {
      $area = ReservServiceLocator::area($this->area_id);
    }
    $weekday = CalendarHelper::getWeekdayByUnixtime(strtotime($this->date));
    //Скидки клиента
    if ($client->discount !== null && $client->discount != 0) {
      $discount = new DiscountsModel($client->discount);
      if (!$discount->checkError) {
        $retail             = round($discount->dimension == 1 ? $price / 100 * $discount->retail : $discount->retail, 2);
        $price              -= $retail;
        $prices['discount'] = -$retail;
      }
    }
    $time_price = $price;
    //Новая фича
    //Если выбрали что чувак играет по спец цене то, пипец все обнуляется и берется цена указаная для этой спец цены
    $clientSpecPrices = ReservServiceLocator::priceOptions()->getSpecPrices($client, $this->date, $this->times, $area);
    if (array_key_exists((string)$this->sprice_id, $clientSpecPrices)) {
      /** @var ReservationSpecPriceDto $spec_price */
      $spec_price = $clientSpecPrices[$this->sprice_id];
      if (!$spec_price->duration || array_key_exists(TimeHelper::convertTime24($time), $spec_price->durations[$weekday])) {
        $price                = $spec_price->rate;
        $prices['spec_price'] = $spec_price->rate;
      } else {
        $prices['spec_price'] = null;
      }
    }
    //Выбираем ставку lettercode для наценки (если есть)
    $clientStocks    = ReservServiceLocator::priceOptions()->getStocks($client, $this->date, $this->times, $area);
    $prices['stock'] = null;
    $priceStocks     = [];
    foreach ($this->getStocks() as $stock_id) {
      $stock_id = (int)$stock_id;
      if (array_key_exists($stock_id, $clientStocks)) {
        /** @var ReservationStockDto $stock */
        $stock = $clientStocks[$stock_id];
        if (!$stock->duration || array_key_exists(TimeHelper::convertTime24($time), $stock->durations[$weekday])) {
          if ($stock->rate < 0) {
            // новая фича если используются stock и спеццены одновременно и сток меньше нуля, то спец цена не используется, а опция как скидка
            $price                = $time_price;
            $prices['spec_price'] = null;
          }
          $rate                   = $this->addStockRate[$stock_id] && $stock->onlyOnce ? 0 : $stock->rate;
          $rate                   = (float)($stock->dimension === '2' ? (($price * $rate) / 100) : $rate);
          $priceStocks[$stock_id] = $rate;
          ++$this->addStockRate[$stock_id];
        }
      }
    }
    $prices['stock'] = array_sum($priceStocks);
    $price           += array_sum($priceStocks);
    if (!empty($priceStocks)) {
      $prices['pricesStocks'] = $priceStocks;
    }

    return $price;
  }

  /**
   * getSumPriceForStateLHN
   *
   * Получает сумму цен за свет, тепло и сетку.
   *
   * @param string $time               Время
   * @param bool   $useFromReservation Использовать данные из бронирования
   *
   * @return int
   */
  public function getSumPriceForStateLHN($time, $useFromReservation = false)
  {
    $baseStatePrices = $useFromReservation ? $this : $this->areas;

    $sum_price = 0;
    foreach (ReservServiceLocator::webIo()->getWebIoTypes() as $state => $state_data) {
      if (($useFromReservation && $this->{$state . '_state'}) || $this->checkWebIoStateByTime($state, $time)) {
        $sum_price                                     += $baseStatePrices->{$state . '_price'};
        $this->sum_price_array[$time]['webIo'][$state] = (float)$baseStatePrices->{$state . '_price'};

        $this->state_sum_price[$state][$time]       = (float)$baseStatePrices->{$state . '_price'};
        $this->state_sum_price[$state]['sum_price'] += (float)$baseStatePrices->{$state . '_price'};
      } else {
        $this->sum_price_array[$time]['webIo'][$state] = (float)0;
      }
    }
    $this->state_sum_price['all'][$time]       = (float)$sum_price;
    $this->state_sum_price['all']['sum_price'] += (float)$sum_price;

    return $sum_price;
  }

  /**
   * Проверяет, активно ли состояние WebIo (свет, отопление, сетка) для заданного времени.
   *
   * @param string $aliasState Сокращение состояния (light, heating, net)
   * @param string $time       Время проверки
   *
   * @return bool
   */
  public function checkWebIoStateByTime($aliasState, $time)
  {
    return $this->areas->{$aliasState . '_on'}
      && (WebIoModel::issetWebIoStateByAreaIdWeekdayTime($this->area_id, $this->date,
          ReservServiceLocator::webIo()->getWebIoTypes()[$aliasState]['id'], $time)
        || (isset($this->webIoStates[$aliasState . '_state']) && $this->webIoStates[$aliasState . '_state'][$time] == 1));
  }

  /**
   * Устанавливает начальные значения суммы стоимости состояний WebIo.
   *
   * @return void
   */
  private function setDefaultStateSumPrice()
  {
    $this->state_sum_price['all']['sum_price'] = 0;
    foreach (ReservServiceLocator::webIo()->getWebIoTypes() as $state => $state_data) {
      $this->state_sum_price[$state]['sum_price'] = 0;
      foreach ($this->times as $time) {
        $this->state_sum_price[$state][$time] = 0;
        $this->state_sum_price['all'][$time]  = 0;
      }
    }
  }

  /**
   * Процесс вставки бронирования в базу данных.
   *
   * @param mixed &$error_code Переменная для хранения кода ошибки
   *
   * @return bool
   */
  public function insertReservation(&$error_code)
  {
    // снимаем деньги с клиента если нужно
    if ($transactionId = $this->requestPayment($error_code)) {
      //Вытаскиваем код двери
      $this->getDoorCodes();
      // вставляем в базу новую запись о бронировании
      if ($this->insert($error_code)) {
        $this->makePayment($transactionId, ['relatedId' => $this->inserted_reservation_id]);

        return true;
      }
      $this->makePayment($transactionId, [], 'failed');
    }

    return false;
  }

  /**
   * Проверить что способ оплаты личный счет
   *
   * @return bool
   */
  protected function isPaymentFromPrivateAccount(): bool
  {
    return PaymentHelper::isPrivateAccount($this->encash) && $this->pay_status == 1;
  }

  /**   * Проверить что способ оплаты PayPal
   *
   * @return bool
   */
  protected function isPaymentFromPayPal(): bool
  {
    return PaymentHelper::isPayPal($this->encash) && $this->pay_status == 1;
  }

  /**
   *  Провести оплату, по умолчанию если личный счет проводим со статусом успешно
   *
   * @param        $transactionId
   * @param array  $relatedData
   * @param string $status
   *
   * @return bool
   */
  protected function makePayment($transactionId, array $relatedData = [], string $status = 'succeeded'): bool
  {
    if (is_int($transactionId) && $this->isPaymentFromPrivateAccount()) {
      return ModCommHelper::callSafe('clients', 'PrivateAccount/changeStatus',
        ['transactionId' => $transactionId, 'status' => $status, ...$relatedData])->isSuccess();
    }

    return true;
  }

  /**
   * Вставляет данные модели в базу данных.
   *
   * @param mixed &$error_code Переменная для хранения кода ошибки
   *
   * @return bool
   */
  public function insert(&$error_code = false)
  {
    if (parent::insert($error_code)) {
      return true;
    }

    return false;
  }

  /**
   * Удаляет бронирование из базы данных.
   *
   * @param mixed & $error_message          Переменная для хранения сообщения об ошибке
   * @param bool    $mail                   Нужно ли отправлять уведомление на почту
   * @param ?string $tmpPaymentOnlineStatus - для проверки возврата денег если онлайн оплата не прошла
   *
   * @return bool
   */
  public function removeReservation(&$error_message, bool $mail = true, ?string $tmpPaymentOnlineStatus = 'OK'): bool
  {
    // todo двойной запрос одного и того же бронирования - избыточно
    if ($this->engine->getReservationData($this->area_id, $this->date . ' ' . $this->time, $reservation_data)) {
      $this->engine->getReservationDataById($reservation_data['reservation_id'], $reservations, false, true);
      $time_start  = date('H:i', strtotime($reservation_data['start']));
      $time_finish = date('H:i', strtotime($reservation_data['finish']));
      if (is_array($reservations) && count($reservations) > 0) {
        $time_finish = date('H:i', strtotime($reservations[count($reservations) - 1]['finish']));
      }
      $this->timeTitle      = $time_start . ' - ' . $time_finish;
      $client_id            = !empty($reservation_data['main_client_id']) ? $reservation_data['main_client_id']
        : $reservation_data['client_id'];
      $this->current_client = new ClientsModel($client_id);
      $this->setData($reservation_data);
      // получить сумму за игру
      $price = $this->getPriceRemoveReservation($time_start);

      if ($this->remove()) {
        $this->refundPayment($price, $error_message, $tmpPaymentOnlineStatus);
        if ($mail) {
          $this->mailAfterRemoveReservation();
        }

        return true;
      }
      $error_message = '<span class="info">' . lang('fail', 'message_error') . '</span>';
    } else {
      $error_message = '<span class="info">' . lang('order not found', 'message_error') . '</span>';
    }

    return false;
  }

  /**
   * Получает общую стоимость бронирования.
   *
   * @param string $time Время начала бронирования
   *
   * @return float
   */
  public function getPriceRemoveReservation($time)
  {
    $this->sum_price_array[$time]              = $this->setStartPricesBlock();
    $totalCost                                 = $this->price + $this->getSumPriceForStateLHN($this->time, true);
    $this->sum_price_array[$time]['totalCost'] = $totalCost;

    return $totalCost;
  }

  /**
   * Получает код двери для бронирования.
   *
   * @return mixed|bool|null
   */
  public function getDoorCodes()
  {
    $this->door_code = $this->engine->door_code->getClientCode(
      $this->area_id,
      CalendarHelper::getWeekdayByUnixtime(strtotime($this->date)),
      $this->time
    );

    return $this->door_code;
  }

  /**
   * Снимает средства с клиента при бронировании.
   *  Todo добавить обработку ошибок
   *
   * @param mixed &$error_code Переменная для хранения кода ошибки
   */
  public function requestPayment(mixed &$error_code)
  {
    //снимаем бабло со счета, если платит гутхабен и заказ считается завершенным
    if ($this->isPaymentFromPrivateAccount()) {
      $price = $this->getFullPrice();
      if (abs($price) > abs($this->current_client->prepayment_sum)) {
        //оплата недоступна сумма превышает баланс
        $this->price                         = null;
        $this->inserted_reservation_id       = null;
        $error_code                          = 'p1';
        $this->reservation_insert_error_code = 'p1';

        return false;
      }
      if ($price == 0) {
        return true;
      }

      $response = ModCommHelper::callSafe('clients', 'PrivateAccount/addWithdraw', [
        'client_id'    => (int)$this->current_client->client_id,
        'type_code'    => 'reservation_created_private_account',
        'amount'       => $price,
        'related_data' => [
          'date'    => $this->date,
          'time'    => TimeHelper::generateTitleByTimeAndPeriod($this->time, $this->areas->period),
          'area_id' => $this->area_id,
        ],
        'related_id'   => null,
      ]);

      if ($response->isSuccess()) {
        return $response->getDataValue('transaction_id');
      }
      $error_code = $response->getMessage() . '<br>' . implode('<br>', $response->getDataValue('errors', []));

      return false;
    }

    return true;
  }

  /**
   * Возвращает средства клиенту после удаления бронирования.
   * Todo доработать обработку ошибок если не получилось вернуть деньги
   *
   * @param float|null $price Сумма к возврату
   * @param ?string    $error_message
   * @param ?string    $tmpPaymentOnlineStatus
   *
   * @return bool
   */
  public function refundPayment(?float $price = null, ?string &$error_message = null, ?string $tmpPaymentOnlineStatus = 'OK'): bool
  {
    // вернуть оплату на личный счет
    // Если оплата по личному счету или оплата по paypal
    if ($this->isPaymentFromPrivateAccount() || ($this->current_client->refund_for_paypal && $this->isPaymentFromPayPal() && $tmpPaymentOnlineStatus === 'OK')) {
      $price = $price ?? $this->getFullPrice();
      $re    = ModCommHelper::callSafe('clients', 'PrivateAccount/deposit',
        [
          'client_id'    => (int)$this->current_client->client_id,
          'type_code'    => 'reservation_removed_' . PaymentHelper::getAliasPaymentMethod($this->encash),
          'amount'       => $price,
          'related_data' => [
            'date'    => $this->date,
            'time'    => TimeHelper::generateTitleByTimeAndPeriod($this->time, $this->areas->period),
            'area_id' => $this->area_id,
          ],
          'related_id'   => (int)$this->reservation_id,
        ]
      );

      if (!$re->isSuccess()) {
        $error_message = $re->getMessage();

        return false;
      }
    }

    return true;
  }

  /**
   * Отправка писем после успешного бронирования.
   *
   * @return void
   * @todo переделать в отдельный класс
   *  для tennis-centrum-harburg.de используем 'order_new_no_code' если в бронировании не используются дверные коды
   *
   */
  public function mailAfterInsertReservation()
  {
    $template_alias = 'order_new_'
      . (Service::mailer()->getTemplatesEngine()->checkTemplate(ModeTemplate::USER->value,
        'order_new_no_code') && empty($this->getDoorCodesByTimesAsTimeCode())
        ? 'no_code'
        : $this->areas_types->alias . '_' . $this->areas_types->type_id . '_' . $this->areas_sports->sport_id);
    if (USE_AUTHORIZATION && !$this->online_pay) {
      $this->sendMailByTemplateAlias($template_alias);
    }
  }

  /**
   * Отправляет письмо по шаблону.
   *
   * @param string $template_alias Алиас шаблона письма
   *
   * @return void
   */
  public function sendMailByTemplateAlias($template_alias)
  {
    $mailer = Service::mailer();
    if (!empty($this->street_friends)) {
      if (!is_array($this->street_friends)) {
        $this->street_friends = unserialize($this->street_friends, ['allowed_classes' => false]);
      }
    }
    $client   = (empty($this->current_client)) ? new ClientsModel('current') : $this->current_client;
    $tpl_data = $this->generateTemplateKeys($client, $this->street_friends);
    if ($this->checkSendMailAdmin()) {
      $templateAlias = $mailer->getTemplatesEngine()->correctAliasByTime($this->time_start, $template_alias, ModeTemplate::ADMIN->value);
      $emails        = explode(',', Service::configDB('email', 'notify_email'));
      if ((defined('SEND_ADMIN_TO_DIFFERENT_EMAIL') && SEND_ADMIN_TO_DIFFERENT_EMAIL)) {
        $emailsTmp = JsonHelper::decode(Service::configDB('email', 'admins_mail_for_different_areas'));
        foreach ($emailsTmp as $emailData) {
          if (in_array($this->type_id . '_' . $this->sport_id, $emailData->courts)) {
            $emails = $emailData->email;
          }
        }
      }
      $mailer->dispatch(
        $emails,
        ModeTemplate::ADMIN->value,
        $templateAlias,
        $tpl_data
      );
    }
    if ($this->checkSendMailPlayer()) {
      $template_alias = $mailer->getTemplatesEngine()->correctAliasByTime($this->time_start, $template_alias, ModeTemplate::USER->value);
      if (!empty($client->email)) {
        $mailer->dispatch(
          $client->email,
          ModeTemplate::USER->value,
          $template_alias,
          $tpl_data,
          config('lang')->getCurrentLang()
        );
      }
      if (!empty($this->street_friends)) {
        foreach ($this->street_friends as $friend) {
          $client = new ClientsModel($friend['id']);
          if (!empty($client->email)) {
            $mailer->dispatch(
              $client->email,
              ModeTemplate::USER->value,
              $template_alias,
              $this->generateTemplateKeys($client, $this->street_friends),
              config('lang')->getCurrentLang()
            );
          }
        }
      }
    }
  }

  /**
   * Проверяет, нужно ли отправлять письмо игроку.
   *
   * @return bool
   */
  public function checkSendMailPlayer(): bool
  {
    return config('letterTemplates')?->checkSendMailByType($this->areas_types->type_id);
  }

  /**
   * Проверяет, нужно ли отправлять письмо админу.
   *
   * @return bool
   */
  public function checkSendMailAdmin(): bool
  {
    return (bool)Service::configDB('email', 'order_notify')
      || (defined('SEND_ADMIN_TO_DIFFERENT_EMAIL') && SEND_ADMIN_TO_DIFFERENT_EMAIL && !empty(Service::configDB('email',
          'admins_mail_for_different_areas')));
  }

  /**
   * Генерирует ключи шаблона для письма.
   *
   * @param ClientsModel $client  Клиент
   * @param array        $friends Массив друзей
   *
   * @return array
   */
  public function generateTemplateKeys(ClientsModel $client, $friends = [])
  {
    $tpl_data                   = [];
    $tpl_data['CLIENT_NAME']    = (!empty($client->name)) ? $client->name : $this->client_name;
    $tpl_data['CLIENT_SURNAME'] = (!empty($client->surname)) ? $client->surname : $this->client_surname;
    $tpl_data['EMAIL']          = $client->email;

    //данные площадки
    $tpl_data['PLACE_TYPE']  = $this->engine->areas->getTitleByAreaId($this->area_id, 'title_site_url');
    $tpl_data['PLACE_TITLE'] = $this->areas->title;
    $tpl_data['PRICE']       = number_format($this->getTotalCostOrder(), 2, ',', '') . " " . CURR_VALUTE;

    //Данные о типе оплаты (если наличные то вставляем следующий текст)
    $tpl_data['PAYPAL'] = '';
    $tpl_data['ENCASH'] = '';
    if ($this->encash == 1) {
      $tpl_data['ENCASH'] = lang('Invoice payment');
    } else {
      if ($this->encash == 2) {
        $tpl_data['ENCASH'] = lang('Credit balance');
      } else {
        if ($this->encash == 3) {
          $aliasLangPayment   = config('payment')->usePayonePayment() ? 'PAYONE' : 'PAYPAL';
          $tpl_data['ENCASH'] = lang($aliasLangPayment . ' PAYMENT', 'order');
          $tpl_data['PAYPAL'] = lang($aliasLangPayment . ' PAYMENT', 'order');
        } else {
          $tpl_data['ENCASH'] = lang('Thank you for your booking. You are registered with us as a cash payer. The price is.',
            'order', ['price' => NumberHelper::format($this->getTotalCostOrder()), 'currency' => CURR_VALUTE]);
        }
      }
    }

    //данные акции

    if (!empty($this->stock_id)) {
      $stocks = [];
      foreach ($this->getStocks() as $stock_id) {
        $stock    = ReservServiceLocator::priceOptions()->stock($stock_id);
        $stocks[] = (!empty($stock->code) ? "[{$stock->code}] " : '') . ($stock->title);
      }
      $tpl_data['STOCK_CODE']  = implode(' | ', $stocks);
      $tpl_data['STOCK_TITLE'] = '';
      $tpl_data['STOCKS']      = implode(' | ', $stocks);
    } else {
      $tpl_data['STOCK_CODE']  = lang('no');
      $tpl_data['STOCK_TITLE'] = '';
      $tpl_data['STOCKS']      = lang('no');
    }

    $tpl_data['ADDITIONAL_MESSAGE_AFTER_BOOKING'] = $this->additionalMessageAfterBooking();


    //комментарий
    $tpl_data['MEMO'] = $this->memo;

    //дата и промежуток заказа
    $unix_time          = strtotime($this->date);
    $tpl_data['DATE']   = date('d.m.Y',
        $unix_time) . ', ' . TranslateHelper::translateWeekDay(CalendarHelper::getWeekdayByUnixtime($unix_time));
    $tpl_data['PERIOD'] = "\n<br>\t\t" . implode(' ' . lang('clock') . "\n<br>\t\t", array_keys($this->timeTitles));

    $tpl_data['DOOR_CODE'] = $this->getDoorCodesByTimesAsTimeCode();

    $tpl_data['CLIENT_1'] = $this->current_client->name . ' ' . $this->current_client->surname;
    for ($i = 2; $i <= config('DoubleGame')->numberPlayers($this->type_id, $this->sport_id, $this->area_id); $i++) {
      $tpl_data['CLIENT_' . $i] = !empty($friends[$i]['name']) ? $friends[$i]['name'] : lang('no_player_added');
    }

    return $tpl_data;
  }

  /**
   * Отправляет письмо после удаления бронирования.
   *
   * @return void
   * @todo Вынести в отдельный класс
   */
  public function mailAfterRemoveReservation()
  {
    $template_alias = 'order_remove';
    if (USE_AUTHORIZATION) {
      $this->sendMailByTemplateAlias($template_alias);
    }
  }

  /**
   * Выбор спорта для тачскрина.
   *
   * @param int|null $type_id ID типа площадки
   *
   * @return array|bool
   */
  public function selectSport($type_id = null)
  {
    if ($type_id == null) {
      $type_id = $this->type_id;
    }

    return AreasModel::selectSportUseType($type_id);
  }

  /**
   * Формирует заголовки страниц.
   *
   * @param array &$pages Массив страниц
   *
   * @return void
   */
  protected function getPageTitle(&$pages)
  {
    foreach ($pages as &$page) {
      $arrTitle = explode(',', $page['page_title']);
      $expl1    = explode(' ', trim($arrTitle[0]));
      $expl2    = explode(' ', trim(end($arrTitle)));
      if ($expl1[0] == $expl2[0]) {
        $title = $arrTitle[0];
        $title .= ' - ' . end($expl2);
      } else {
        $title = $arrTitle[0];
        $title .= ' - ' . end($arrTitle);
      }
      $page['page_title'] = $title;
    }
  }

  /**
   * Формирует страницы для выбора.
   *
   * @return array
   */
  public function selectPage()
  {
    $perPage = $this->getPerPage();
    $areas   = AreasModel::selectPageUseTypeSport($this->type_id, $this->sport_id);
    $page    = [];
    $ind     = 0;
    foreach ($areas as $i => $area) {
      $ind = ceil(($i + 1) / $perPage);
      if (($i + 1) % $perPage == 0) {
      }
      if (!isset($page[$ind]['page_title'])) {
        $page[$ind]['page_title'] = '';
      }
      $page[$ind]['page_title']  .= $area->area_title . ', ';
      $page[$ind]['page']        = $ind;
      $page[$ind]['sport_title'] = $area->title;
      if (($i + 1) % $perPage == 0) {
        $page[$ind]['page_title'] = trim($page[$ind]['page_title'], ', ');
      }
    }
    if ($ind !== 0) {
      $page[$ind]['page_title'] = trim($page[$ind]['page_title'], ', ');
    }
    $this->getPageTitle($page);

    return $page;
  }

  /**
   * Загружает данные бронирования по ID.
   *
   * @param int  $id   ID бронирования
   * @param bool $tmp  Флаг временной модели
   * @param bool $main Флаг основной модели
   *
   * @return void
   */
  public function setReservationById($id, $tmp = false, $main = false)
  {
    $this->engine->getReservationDataById($id, $data, $tmp, $main);
    $this->load($data);
    $this->current_client = new ClientsModel($data['client_id']);
    $this->encash         = (int)$data['encash'];
    $this->date           = date('Y-m-d', strtotime($this->start));
    $this->time           = date('H:i', strtotime($this->start));
    $this->time_start     = date('H:i', strtotime($this->start));
    $this->time_finish    = date('H:i', strtotime($this->finish));
    if (isset($data['pay_one_type'])) {
      $this->setPaymentOnlineType($data['pay_one_type']);
    }
  }

//  /**
//   * Получает цены по временам и клиенту.
//   *
//   * @return array
//   */
//  public function getPricesByTimesAndClient()
//  {
//    $prices = [];
//    foreach ($this->areas->getAreaPricesByDateTime($this->date, $this->times) as $time => $price) {
//      $prices[$time] = [
//        'price'      => $price,
//        'extra'      => $this->current_client->extra[$this->type_id . '_' . $this->sport_id]->rate,
//        'discount'   => $this->current_client->discounts,
//        'stock'      => $this->stock_id !== null ? $this->current_client->stock[$this->stock_id] : null,
//        'spec_price' => $this->sprice_id !== null ? $this->current_client->spec_price[$this->sprice_id] : null,
//        'webIo'      => $this->areas->getWebIoPriceAndState($this->date, $time)
//      ];
//    }
//
//    return $prices;
//  }

  /**
   * Устанавливает начальную цену по времени.
   *
   * @param string $time Время
   *
   * @return void
   */
  public function setStartPriceByTime($time)
  {
    $this->sum_price_array[$time] = $this->setStartPricesBlock();
  }

  /**
   * Возвращает структуру начальных цен.
   *
   * @return array
   */
  protected function setStartPricesBlock(): array
  {
    $webIo = [];
    foreach (array_keys(ReservServiceLocator::webIo()->getWebIoTypes()) as $alias) {
      $webIo[$alias] = 0;
    }

    return [
      'price'      => 0,
      'extra'      => 0,
      'discount'   => 0,
      'stock'      => 0,
      'spec_price' => 0,
      'sum_price'  => 0,
      'totalCost'  => 0,
      'webIo'      => $webIo,
    ];
  }

  /**
   * Рендерит блок цен.
   *
   * @return array
   */
  public function renderPricesBlock()
  {
    $block_prices = $this->setStartPricesBlock();
    foreach ($this->sum_price_array as $time => $prices) {
      foreach ($prices as $key => $value) {
        if (isset($block_prices[$key])) {
          if ($key !== 'webIo') {
            $block_prices[$key] += $value;
          } else {
            foreach ($value as $key_st => $val_st) {
              $block_prices[$key][$key_st] += $val_st;
            }
          }
        }
      }
    }
    $block_prices['sum_price'] = $this->price;
    foreach ($block_prices as $key => $price) {
      if ($key !== 'webIo') {
        $block_prices[$key] = (in_array(
            $key,
            ['price', 'sum_price']
          ) ? '' : ($key !== 'spec_price' ? ($price >= 0 ? ' + ' : ' - ') : ''))
          . NumberHelper::format(abs($price)) . ' ' . CURR_VALUTE;
      } else {
        foreach ($price as $alias => $st_price) {
          $block_prices[$key][$alias]
            = [
            'price'  => ' + ' . NumberHelper::format($st_price)
              . ' ' . CURR_VALUTE,
            'hidden' => $st_price == 0,
          ];
        }
      }
    }
    $block_prices['text_button'] = defined('USE_NEW_STRATEGY_IF_SUM_NULL') && USE_NEW_STRATEGY_IF_SUM_NULL && $this->price == 0
      ? lang('Book now', 'show_order')
      : lang(
        'show_order_button_submit',
        'show_order',
        ['sum_price' => NumberHelper::format($this->price), 'currency' => CURR_VALUTE]
      );

    return $block_prices;
  }

  /**
   * Устанавливает состояние WebIo.
   *
   * @return void
   */
  protected function setStateWebIo()
  {
    foreach (array_keys(WebIoTypeSatesModel::getTypeSates('alias', true, false, true)) as $alias) {
      $states = Service::request()->validated($alias . '_state', 'request',
        [['time', ['allowArray' => true, 'arrayOnly' => true]]], []);
      foreach ($states as $time => $state) {
        $this->webIoStates[$alias . '_state'][$time] = $state;
      }
    }
  }

  /**
   * Устанавливает игроков по времени.
   *
   * @return void
   */
  protected function setPlayersByTime()
  {
    foreach ($this->times as $time) {
      $this->playersByTime[$time] = $this->current_client->client_id;
    }
  }

  /**
   * Устанавливает заголовки временных промежутков.
   *
   * @return void
   */
  public function setTimeTitles()
  {
    $this->timeTitles = TimeHelper::getTimeByStartFinish($this->times, $this->areas->period, false, true);
  }

  /**
   * Получает коды дверей в виде строки.
   *
   * @return string
   */
  protected function getDoorCodesByTimesAsTimeCode()
  {
    $result = '';
    foreach ($this->door_codes as $door_code) {
      $result .= "\n<br>\t\t<span>" . $door_code->time . ' ' . lang('clock') . ':   ' . $door_code->code . '</span>';
    }

    return $result;
  }

  /**
   * Получает дополнительное сообщение после бронирования.
   *
   * @param float $sum               Общая сумма
   * @param int   $cancellation_days Количество дней для отмены
   *
   * @return string
   */
  public function getMessageAfterBooking($sum, $cancellation_days)
  {
    if ($this->barClient) {
      $prefix = '_guest';
    } else {
      switch ((int)$this->encash) {
        case 0:
          $prefix = '_cash_payer';
          break;
        case 1:
          $prefix = '_billing_customer';
          break;
        case 2:
          $prefix = '_credit';
          break;
        case 3:
          $prefix = '_paypal';
          break;
        default:
          $prefix = '';
          break;
      }
    }

    return langByAreaType('info_text_after_booking' . $prefix, $this->areas_types->alias, $this->areas_types->type_id, [
        'price'             => number_format(
            $sum,
            2,
            ',',
            ''
          ) . ' ' . CURR_VALUTE,
        'cancellation_days' => $cancellation_days,
      ]) . '<br>' . $this->additionalMessageAfterBooking();
  }

  /**
   * Получает полную цену с учетом всех состояний.
   *
   * @return float
   */
  public function getFullPrice()
  {
    $price = $this->price;
    foreach (array_keys(ReservServiceLocator::webIo()->getWebIoTypes()) as $state) {
      $price += $this->{$state . '_price'};
    }

    return $price;
  }

  /**
   * Получает общую стоимость заказа.
   *
   * @return float
   */
  public function getTotalCostOrder()
  {
    $sum = 0;
    if (empty($this->sum_price_array) || count($this->sum_price_array) !== count($this->times)) {
      return $this->setTotalCostPayment($check, $error);
    }

    foreach ($this->sum_price_array as $prices) {
      $sum += $prices['totalCost'];
    }

    return $sum;
  }

  /**
   * Проверяет минимальное количество периодов.
   *
   * @return bool
   */
  public function checkMinCountPeriod()
  {
    $device_type = 'pc';
    if ($this->touch) {
      $device_type = 'touch';
    }

    return $this->engine->rules->checkMinCountPeriod($this->area_id, $this->type_id, $this->sport_id,
      $this->current_client->club_state, $device_type, $this->date);
  }

  /**
   * Получает количество записей на странице.
   *
   * @return int
   */
  protected function getPerPage()
  {
    return $this->touch ? ($this->areas_types->alias == 'open' ? OPEN_STREET_PER_PAGE : TOUCH_PER_PAGE)
      : ($this->admin ? ADMIN_COUNT_COURT_ON_PAGE : PER_PAGE);
  }

  /** Включить свет если бронируют время которое уже идет
   *
   * @param       $error
   * @param array $states
   */
  public function switchStateLHN(&$error, $states = ['light' => 1, 'heating' => 2, 'net' => 3])
  {
    $unix_time = strtotime($this->date . ' ' . $this->time);
    // если тот самый миг то включаем свет если требуется, кроме paypal  он включается отдельно если подтвержено
    if (!$this->online_pay
      && date('Y-m-d') == date('Y-m-d', $unix_time)
      && date('H:i') >= date('H:i', $unix_time)) {
      $electricity_action = [];
      foreach ($states as $state => $type) {
        if ($this->{$state . '_state'} !== null && $this->{$state . '_state'} == 1) {
          $electricity_action[$this->area_id][$type] = 1;
        }
      }
      $this->engine->webIo->areasElectricityProcess($electricity_action, $error);
    }
  }

  /**
   * Возвращает тип устройства, с которого был сделан запрос.
   *
   * @return string 'touch' если запрос от тачскрина, иначе 'pc'
   */
  public function getDeviceType(): string
  {
    return $this->touch ? 'touch' : 'pc';
  }

  /**
   * Получает дополнительное сообщение после бронирования, если оно доступно.
   *
   * @return string Дополнительное сообщение или пустая строка, если сообщение недоступно
   */
  protected function additionalMessageAfterBooking(): string
  {
    $out = [];
    foreach ($this->getStocks() as $stockId) {
      $out[] = ModCommHelper::get('stocks', 'stocks/getPreferencesForGroupConditions', ['stock_id' => $stockId, 'preferenceId' => 'MWB'], 'data', '');
    }

    return implode("\n", array_unique($out));
  }

  protected function getPriceOptions(): array
  {
    $client = ReservServiceLocator::client($this->client_id);
    $area   = ReservServiceLocator::area($this->area_id);

    return ReservServiceLocator::priceOptions()->getPriceOptions($client, $this->date, $this->times, $area);
  }

  public function getClientId(): string
  {
    return $this->client_id ?? md5($this->generatePlayerName($this->current_client->name, $this->current_client->surname) . '|' . $this->current_client->email);
  }

  /**
   *  Сгенерировать имя игрока, по фамилии и имени
   *
   * @param null|string $name
   * @param null|string $surname
   * @param null|string $prefix - добавочный префикс
   *
   * @return string
   */
  public function generatePlayerName(?string $name = null, ?string $surname = null, ?string $prefix = null): string
  {
    $player_name = [];
    if (!empty($surname)) {
      $player_name[] = $surname;
    }
    if (!empty($name)) {
      $player_name[] = $name;
    }
    $player_name = implode(' ', $player_name);
    if (empty($player_name)) {
      $player_name = lang('Guest_player');
    }

    return addslashes($player_name . ($prefix !== null ? $prefix : ''));
  }
}
