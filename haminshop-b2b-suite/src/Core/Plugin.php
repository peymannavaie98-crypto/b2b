<?php
namespace HaminShop\B2BSuite\Core;

class Plugin {
    private static ?Plugin $instance = null;
    private Container $container;
    private EventDispatcher $eventDispatcher;

    private function __construct() {
        $this->container = new Container();
        $this->eventDispatcher = new EventDispatcher();
    }

    public static function getInstance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // Register core services in the container
        $this->registerServices();

        // Load text domain
        load_plugin_textdomain(
            'haminshop-b2b-suite',
            false,
            dirname(plugin_basename(HAMINSHOP_B2B_FILE)) . '/languages/'
        );

        // Hook early events
        add_action('init', [$this, 'onWordPressInit']);
    }

    private function registerServices(): void {
        // We will register Repositories, Use Cases, Modules, etc. here
        // Example:
        // $this->container->set(SomeInterface::class, fn() => new SomeImplementation());
    }

    public function onWordPressInit(): void {
        // Initialize Modules, Roles, REST API
        $roleManager = new \HaminShop\B2BSuite\Application\ACL\RoleManager();
        $roleManager->registerRoles();

        // Register REST API Endpoints
        add_action('rest_api_init', function () {
            (new \HaminShop\B2BSuite\HTTP\REST\V1\CustomerController())->register_routes();
            (new \HaminShop\B2BSuite\HTTP\REST\V1\QuoteController())->register_routes();
            (new \HaminShop\B2BSuite\HTTP\REST\V1\PaymentController())->register_routes();
            (new \HaminShop\B2BSuite\HTTP\REST\V1\RMAController())->register_routes();
        });

        // Initialize Modules
        (new \HaminShop\B2BSuite\Modules\Customer\OnboardingModule())->init();
        (new \HaminShop\B2BSuite\Modules\PricingEngine\PricingModule())->init();
        (new \HaminShop\B2BSuite\Modules\Order\QuoteModule())->init();
        (new \HaminShop\B2BSuite\Modules\Payment\PaymentModule())->init();
        (new \HaminShop\B2BSuite\Modules\Logistics\WarehouseModule())->init();
        (new \HaminShop\B2BSuite\Modules\O2O\O2OModule())->init();
        (new \HaminShop\B2BSuite\Modules\Marketing\RestockModule())->init();
        (new \HaminShop\B2BSuite\Modules\Finance\CashbackModule())->init();
        (new \HaminShop\B2BSuite\Modules\Compliance\SamaneModyanModule())->init();

        // Initialize UI Integration
        (new \HaminShop\B2BSuite\Admin\AdminMenu())->init();
        (new \HaminShop\B2BSuite\Frontend\FrontendManager())->init();

        $this->eventDispatcher->dispatch('plugin_initialized', []);
    }

    public function getContainer(): Container {
        return $this->container;
    }

    public function getEventDispatcher(): EventDispatcher {
        return $this->eventDispatcher;
    }
}
