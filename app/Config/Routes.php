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
    $routes->put('user/update/profile', 'API\\User::updateProfile', ['filter' => 'auth']);
    $routes->post('user/update/profile-image', 'API\\User::updateProfileImage', ['filter' => 'auth']);
    $routes->get('user/get/logs', 'API\\User::getUserLoginLogs', ['filter' => 'auth']);

}); 
