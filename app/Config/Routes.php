<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default Home
$routes->get('/', 'Home::index');

// -----------------------------------------------------------------------------
// GLOBAL OPTIONS HANDLER (Fixes all CORS preflight issues)
// MUST be placed BEFORE any groups or filters
// -----------------------------------------------------------------------------
$routes->options('(:any)', function () {
    $response = service('response');

    return $response
        ->setHeader('Access-Control-Allow-Origin', '*')
        ->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With')
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
        ->setStatusCode(200);
});

// -----------------------------------------------------------------------------
// API ROUTES GROUP (CORS filter applied)
// -----------------------------------------------------------------------------
$routes->group('', ['filter' => 'cors'], function ($routes) {

    // -----------------------------
    // AUTH / USER ROUTES
    // -----------------------------
    $routes->add('register', 'API\\User::register');
    $routes->add('login', 'API\\User::login');
    $routes->add('logout', 'API\\User::logout');
    
    $routes->get('checktoken', 'API\\User::checkToken');
    $routes->add('validateOTP', 'API\\User::validateOTPForUser');
    $routes->post('reGenToken', 'API\\Token::reGenToken');

    $routes->add('logout', 'API\\User::logout');

    // -----------------------------
    // CLIENT ROUTES (with auth filter)
    // -----------------------------
    $routes->get('client', 'Client::index', ['filter' => 'auth']);
    $routes->post('client', 'Client::store', ['filter' => 'auth']);
    $routes->get('client/(:num)', 'Client::show/$1', ['filter' => 'auth']);
    $routes->put('client/(:num)', 'Client::update/$1', ['filter' => 'auth']);
    $routes->delete('client/(:num)', 'Client::destroy/$1', ['filter' => 'auth']);

    // -----------------------------
    // MEMBERS / BORROWERS ROUTES
    // -----------------------------
    $routes->get('borrower', 'API\\Borrower::index', ['filter' => 'auth']);
    $routes->post('borrower', 'API\\Borrower::add', ['filter' => 'auth']);
    $routes->put('borrower', 'API\\Borrower::update', ['filter' => 'auth']);
    $routes->delete('borrower', 'API\\Borrower::void', ['filter' => 'auth']);
    $routes->get('borrower/summary', 'API\\Borrower::getSummary', ['filter' => 'auth']);
    $routes->get('borrower/all', 'API\\Borrower::getAll', ['filter' => 'auth']);
    $routes->get('borrower/importdraft', 'API\\Borrower::importDraft', ['filter' => 'auth']);
    $routes->get('borrower/settlement-deficit', 'API\\Loan::getBorrowerSettlementDeficit');    
    
    // -----------------------------
    // LOAN / BORROWERS ROUTES
    // -----------------------------
    $routes->get('loan', 'API\\Loan::index', ['filter' => 'auth']);
    $routes->get('loan/get/payment/report', 'API\\Loan::get_payment_report', ['filter' => 'auth']);
    $routes->post('loan', 'API\\Loan::add', ['filter' => 'auth']);
    $routes->post('loan/add-yearly-settlement', 'API\\Loan::addLoanYearlySettlement', ['filter' => 'auth']);
    $routes->put('loan', 'API\\Loan::update', ['filter' => 'auth']);
    $routes->delete('loan', 'API\\Loan::void', ['filter' => 'auth']);
    $routes->post('loan/approve', 'API\\Loan::approve', ['filter' => 'auth']);
    $routes->post('loan/release', 'API\\Loan::release', ['filter' => 'auth']);
    $routes->post('loan/reject', 'API\\Loan::reject', ['filter' => 'auth']);
    $routes->post('loan/payment', 'API\\Loan::payment', ['filter' => 'auth']);
    $routes->post('loan/payment/void', 'API\\Loan::voidPayment', ['filter' => 'auth']);
    $routes->post('loan/settlement', 'API\\Loan::addSettlement', ['filter' => 'auth']);
    $routes->post('loan/bonus-settlement', 'API\\Loan::addBonusSettlement', ['filter' => 'auth']);
    $routes->post('loan/payment-report/pay', 'API\\Loan::addSalaryPayment', ['filter' => 'auth']);
    $routes->post('loan/bonus-collection/pay', 'API\\Loan::payBonusCollection', ['filter' => 'auth']);
    $routes->post('loan/update-schedule', 'API\\Loan::updateLoanSchedule', ['filter' => 'auth']);
    $routes->get('loan/contract', 'API\\Loan::contractOfLoan');
    $routes->get('loan/addendum', 'API\\Loan::loanAddendum');
    $routes->get('loan/claim/aquisition', 'API\\Loan::loanClaimAquisition');
    $routes->get('loan/claim/aquisition-settlement', 'API\\Loan::settlementAcknowledgement');
    $routes->get('loan/claim/monthly-aquisition-settlement', 'API\\Loan::monthlyPaymentAcknowledgement');
    $routes->post('loan/send-otp', 'API\\Loan::sendLoanOTP');
    $routes->post('loan/validate-otp', 'API\\Loan::validateLoanOTP');
    $routes->get(
        'loan/getBonusCollections',
        'API\Loan::getBonusCollections'
    );
    $routes->get(
        'loan/bonus-collection/details',
        'API\Loan::getBonusPaymentDetails'
    );
    $routes->get(
        'loan/get-bonus-settlement',
        'API\Loan::getBonusSettlementDetails'
    );

    // -----------------------------
    // LOAN PRODUCTS ROUTESapp/Controllers/API/LoanProducts.php
    // -
    // $routes->get('loanproducts', 'API\\LoanProducts::index', ['filter' => 'auth']);
    $routes->get('loanproducts', 'API\\LoanProducts::index', ['filter' => 'auth']);
    
    // -----------------------------
    // LOAN ADJUSTMENTS ROUTESapp/Controllers/API/LoanProducts.php
    // -
    $routes->get('loanadjustments', 'API\\LoanAdjustment::index', ['filter' => 'auth']);
    $routes->post('loanadjustments', 'API\\LoanAdjustment::add', ['filter' => 'auth']);
    $routes->post('loanadjustments/approve', 'API\\LoanAdjustment::approve', ['filter' => 'auth']);
    // -----------------------------
    // MANAGERS VAULT ROUTES app/Controllers/API/ManagerVault.php
    // -
    $routes->get('managervault', 'API\\ManagerVault::index', ['filter' => 'auth']);
    $routes->post('managervault', 'API\\ManagerVault::addTransaction', ['filter' => 'auth']);
    $routes->post('managervault/transfer/cashier', 'API\\ManagerVault::transfer', ['filter' => 'auth']);
    $routes->get('managervault/summary', 'API\\ManagerVault::getSummary', ['filter' => 'auth']);
    $routes->get('managervault/transaction/details', 'API\\ManagerVault::getTransactionDetails', ['filter' => 'auth']);
    $routes->delete('managervault', 'API\\ManagerVault::deleteTransaction', ['filter' => 'auth']);
    // -----------------------------
    // MANAGERS VAULT ROUTES app/Controllers/API/CashierVault.php
    // -
    $routes->get('cashiervault', 'API\\CashierVault::index', ['filter' => 'auth']);
    $routes->get('cashiervault/transaction-details', 'API\\CashierVault::getTransactionDetails', ['filter' => 'auth']);
    $routes->get('cashiervault/transaction-summary', 'API\\CashierVault::getSummary', ['filter' => 'auth']);
    $routes->post('cashiervault', 'API\\CashierVault::cashTransaction', ['filter' => 'auth']);
    $routes->post('cashiervault/return-vault', 'API\\CashierVault::returnToManager', ['filter' => 'auth']);
    $routes->post('cashiervault/approve-return-vault', 'API\\CashierVault::approveDailyClose', ['filter' => 'auth']);
    $routes->post('cashiervault/reject-return-vault', 'API\\CashierVault::rejectDailyClose', ['filter' => 'auth']);
    $routes->delete('cashiervault', 'API\\CashierVault::voidTransaction', ['filter' => 'auth']);

    /*
    |--------------------------------------------------------------------------
    | Cashier Daily Close
    |--------------------------------------------------------------------------
    */
    $routes->get(
        'cashierdailyclose',
        'API\\CashierDailyClose',
        ['filter' => 'auth']
    );
    $routes->get(
        'cashierdailyclose/summary',
        'API\\CashierDailyClose::getSummary',
        ['filter' => 'auth']
    );

    $routes->get(
        'cashierdailyclose/details/(:num)',
        'API\\CashierDailyClose::details/$1',
        ['filter' => 'auth']
    );

    $routes->post(
        'cashierdailyclose/cancel/(:num)',
        'API\\CashierDailyClose::cancel/$1',
        ['filter' => 'auth']
    );

    $routes->get('user/get/cashier', 'API\\User::getCashiers', ['filter' => 'auth']);
    $routes->get('user/get/profile', 'API\\User::getProfile', ['filter' => 'auth']);
    $routes->post('user/otp/change-password', 'API\\User::sendChangePasswordOTP', ['filter' => 'auth']);
    $routes->post('user/change-password', 'API\\User::changePassword', ['filter' => 'auth']);
    $routes->put('user/update/profile', 'API\\User::updateProfile', ['filter' => 'auth']);
    $routes->post('user/update/profile-image', 'API\\User::updateProfileImage', ['filter' => 'auth']);
    $routes->get('user/get/logs', 'API\\User::getUserLoginLogs', ['filter' => 'auth']);

    /*
    |--------------------------------------------------------------------------
    | BORROWER SALARY
    |--------------------------------------------------------------------------
    */

    $routes->get(
        'borrower/salary/get',
        'API\\BorrowerSalary::get',
        ['filter' => 'auth']
    );

    $routes->get(
        'borrower/salary/details/(:num)',
        'API\\BorrowerSalary::details/$1',
        ['filter' => 'auth']
    );

    $routes->post(
        'borrower/salary/save',
        'API\\BorrowerSalary::save',
        ['filter' => 'auth']
    );

    $routes->delete(
        'borrower/salary/delete/(:num)',
        'API\\BorrowerSalary::delete/$1',
        ['filter' => 'auth']
    );

    $routes->post('borrower/salary/bulk-save', 'API\\BorrowerSalary::bulkSave', ['filter' => 'auth']);
    $routes->get('borrower/salary/summary', 'API\\BorrowerSalary::summary', ['filter' => 'auth']);
    

    $routes->get(
        'cashiervault/export',
        'API\CashierVault::export',
        ['filter' => 'auth']
    );
    $routes->get(
        'borrower/cashier-transaction',
        'API\CashierVault::getBorrowerCashierTransactions',
        ['filter' => 'auth']
    );

    /*
    |--------------------------------------------------------------------------
    | BANK ACCOUNTS
    |--------------------------------------------------------------------------
    */

    $routes->get(
        'bankaccounts',
        'API\Bank::index',
        ['filter' => 'auth']
    );

    $routes->get(
        'bankaccounts/details/(:num)',
        'API\Bank::details/$1',
        ['filter' => 'auth']
    );

  

    $routes->put(
        'bankaccounts',
        'API\Bank::update',
        ['filter' => 'auth']
    );

    $routes->delete(
        'bankaccounts',
        'API\Bank::delete',
        ['filter' => 'auth']
    );

    $routes->get(
        'bankaccounts/summary',
        'API\Bank::summary',
        ['filter' => 'auth']
    );



    $routes->get(
        'bankaccounts/transactions',
        'API\Bank::transactions',
        ['filter' => 'auth']
    );
    $routes->post(
        'bankaccounts/transactions',
        'API\Bank::addTransaction',
        ['filter' => 'auth']
    );
    $routes->delete(
        'bankaccounts/close/(:num)',
        'API\Bank::closeBankAccount/$1',
        ['filter' => 'auth']
    );
    $routes->put(
        'bankaccounts/transactions/(:num)',
        'API\Bank::editTransaction/$1',
        ['filter' => 'auth']
    );

    $routes->get(
        'bankaccounts/transactions/(:num)',
        'API\Bank::transactionDetails/$1'
    );
    
    $routes->get(
        'bankaccounts/transactions/dashboard/(:num)',
        'API\Bank::dashboardAccountSummary/$1'
    );

    $routes->delete(
        'bankaccounts/transactions/void/(:num)',
        'API\Bank::voidTransaction/$1'
    );


    $routes->get('bank/banks', 'API\Bank::getBanks');
    $routes->get('bankaccounts/all', 'API\Bank::getBankAccountsAll');
    $routes->get(
        'bank',
        'API\Bank::index',
        ['filter' => 'auth']
    );
    $routes->post(
        'bank',
        'API\Bank::addBank',
        ['filter' => 'auth']
    );
    $routes->put(
        'bank',
        'API\Bank::updateBank',
        ['filter' => 'auth']
    );
    

    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */

    $routes->group('employee', ['filter' => 'auth'], function ($routes) {

        $routes->get('/', 'API\Employee::get');

        $routes->get('summary', 'API\Employee::getSummary');

        $routes->get('details/(:num)', 'API\Employee::details/$1');

        $routes->post('add', 'API\Employee::add');

        $routes->put('update', 'API\Employee::update');

        $routes->delete('void', 'API\Employee::void');

    });


    /*
    |--------------------------------------------------------------------------
    | Employee Schedule
    |--------------------------------------------------------------------------
    */

    $routes->group('employee-schedule', ['filter' => 'auth'],function ($routes) {

        /*
        |--------------------------------------------------------------------------
        | Page
        |--------------------------------------------------------------------------
        */

        $routes->get(
            '/',
            'API\EmployeeSchedule::index'
        );

        /*
        |--------------------------------------------------------------------------
        | CRUD
        |--------------------------------------------------------------------------
        */

        $routes->get(
            'get',
            'API\EmployeeSchedule::get'
        );


        $routes->post(
            'save',
            'API\EmployeeSchedule::save'
        );


        $routes->delete(
            'delete',
            'API\EmployeeSchedule::delete'
        );

        /*
        |--------------------------------------------------------------------------
        | Schedule Days
        |--------------------------------------------------------------------------
        */

        $routes->get(
            'days',
            'API\EmployeeSchedule::getScheduleDays'
        );

        $routes->post(
            'save-days',
            'API\EmployeeSchedule::saveScheduleDays'
        );

        /*
        |--------------------------------------------------------------------------
        | Current Employee Schedule
        |--------------------------------------------------------------------------
        */

        $routes->get(
            'current',
            'API\EmployeeSchedule::getCurrentSchedule'
        );

        $routes->get(
            'dates',
            'API\EmployeeSchedule::getScheduleDates'
        );

    });

    $routes->group('employee-salary', ['filter' => 'auth'], function ($routes) {

        /*
        |--------------------------------------------------------------------------
        | Page
        |--------------------------------------------------------------------------
        */

        $routes->get(
            '/',
            'API\EmployeeSalary::index'
        );

        /*
        |--------------------------------------------------------------------------
        | Salary
        |--------------------------------------------------------------------------
        */

        $routes->get(
            'get',
            'API\EmployeeSalary::get'
        );

        $routes->post(
            'save',
            'API\EmployeeSalary::save'
        );

        $routes->delete(
            'delete',
            'API\EmployeeSalary::delete'
        );

        /*
        |--------------------------------------------------------------------------
        | Salary Effective Dates
        |--------------------------------------------------------------------------
        */

        $routes->get(
            'dates',
            'API\EmployeeSalary::getSalaryDates'
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Employee Government Contribution
    |--------------------------------------------------------------------------
    */

    $routes->group('employee-government', function ($routes) {

        /*
        |--------------------------------------------------------------------------
        | Get
        |--------------------------------------------------------------------------
        */

        $routes->get(

            'get',

            'API\EmployeeGovernment::get'

        );

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $routes->post(

            'save',

            'API\EmployeeGovernment::save'

        );

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $routes->delete(

            'delete',

            'API\EmployeeGovernment::delete'

        );

    });
   
}); 
