<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/**/
Auth::routes();
Route::get('/ownerapilogin', 'OwnerApiLoginController@ShowOwnerLoginForm');
Route::get('/okownerlogin', 'OwnerApiLoginController@PasswordOwnerVerify')->name('homeowner');
Route::get('/okownersearch', 'OwnerApiLoginController@OwnerSearch')->name('ownersearch');
Route::get('/okownersearchpayrolls', 'OwnerApiLoginController@OwnerSearchPayrolls')->name('ownersearchpayrolls');
Route::get('/okownerpayrolls', 'OwnerApiLoginController@OwnerPayrolls')->name('ownerpayrolls');
Route::get('/owner-password', 'OwnerApiLoginController@OwnerPassword')->name('owner-password');
Route::post('/reset-owner-password', 'OwnerApiLoginController@ResetOwnerPassword')->name('resetownerpassword');

Route::get('/employeelogin/{company_idnumber}/{employee_idnumber}', 'EmployeeLoginController@ShowEmployeeLoginForm');
Route::post('/okemployeelogin/{company_idnumber}/{employee_idnumber}', 'EmployeeLoginController@PasswordEmployeeVerify')->name('homeemployees');
Route::get('/employee-password/{company_idnumber}/{employee_idnumber}', 'EmployeeLoginController@EmployeePassword')->name('employee-password');
Route::post('/reset-employee-password/{company_idnumber}/{employee_idnumber}', 'EmployeeLoginController@ResetEmployeePassword')->name('resetemployeepassword');
Route::get('/retrieve-password-employee/{employee_idnumber}', 'EmployeeLoginController@RetrievePasswordEmployee')->name('retrievepasswordemployee');
Route::get('/accept-retrieve-password-employee/{employee_idnumber}/{hash}', 'EmployeeLoginController@AcceptRetrievePasswordEmployee')->name('acceptretrievepasswordemployee');

Route::get('/sellerlogin/{company_idnumber}', 'SellerLoginController@ShowSellerLoginForm');
Route::get('/oksellerlogin/{company_idnumber}', 'SellerLoginController@PasswordSellerVerify')->name('homesellers');
Route::get('/oksellerssearch/{company_idnumber}', 'SellerLoginController@SellersSearch')->name('sellerssearch');
Route::get('/oksellerspayrolls/{company_idnumber}', 'SellerLoginController@SellersPayrolls')->name('sellerspayrolls');
Route::get('/oksellerspayrollssearch/{company_idnumber}', 'SellerLoginController@SellersPayrollsSearch')->name('sellerspayrollssearch');
Route::post('/oksellersdocumentsreception/{company_idnumber}', 'SellerLoginController@SellersDocumentsReceptionView')->name('ok-sellers-documents-reception-view');
Route::post('/sellers-document-reception/{company_idnumber}', 'SellerLoginController@SellersDocumentsReception')->name('sellers-documents-reception');
Route::get('/oksellersradianevents/{company_idnumber}', 'SellerLoginController@SellersRadianEventsView')->name('ok-sellers-radian-events-view');
Route::get('/oksellersradiansearch/{company_idnumber}', 'SellerLoginController@SellersRadianSearch')->name('sellersradiansearch');
Route::get('/seller-password/{company_idnumber}', 'SellerLoginController@SellerPassword')->name('seller-password');
Route::post('/reset-seller-password/{company_idnumber}', 'SellerLoginController@ResetSellerPassword')->name('resetsellerpassword');
Route::get('/retrieve-password-seller/{company_idnumber}', 'SellerLoginController@RetrievePasswordSeller')->name('retrievepasswordseller');
Route::get('/accept-retrieve-password-seller/{company_idnumber}/{hash}', 'SellerLoginController@AcceptRetrievePasswordSeller')->name('acceptretrievepasswordseller');

Route::get('/customerlogin/{company_idnumber}/{customer_idnumber}', 'CustomerLoginController@ShowLoginForm');
Route::post('/okcustomerlogin/{company_idnumber}/{customer_idnumber}', 'CustomerLoginController@PasswordVerify')->name('homecustomers');
Route::get('/customer-password/{company_idnumber}/{customer_idnumber}', 'CustomerLoginController@CustomerPassword')->name('customer-password');
Route::post('/reset-customer-password/{company_idnumber}/{customer_idnumber}', 'CustomerLoginController@ResetCustomerPassword')->name('resetcustomerpassword');
Route::get('/retrieve-password/{customer_idnumber}', 'CustomerLoginController@RetrievePassword')->name('retrievepassword');
Route::get('/accept-retrieve-password/{customer_idnumber}/{hash}', 'CustomerLoginController@AcceptRetrievePassword')->name('acceptretrievepassword');

Route::get('/accept-reject-document/{company_idnumber}/{customer_idnumber}/{prefix}/{docnumber}/{issuedate}', 'AcceptRejectDocumentController@ShowViewAcceptRejectDocument');

Route::get('/listings', 'ListingController@index');
Route::get('/portal', 'ListingController@index');
Route::get('/', 'HomeController@index')->name('home');

Route::group(['middleware' => 'auth'], function() {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/documents', 'HomeController@listDocuments')->name('listdocuments');
    Route::get('/taxes', 'HomeController@listTaxes')->name('listtaxes');
    Route::get('/listconfigurations', 'HomeController@listConfigurations')->name('listconfigurations');

    //configuration
    Route::get('/configuration', 'ConfigurationController@index')->name('configuration_index');
    Route::get('/configuration_admin', 'ConfigurationController@configuration_admin')->name('configuration_admin');
    Route::post('/configuration', 'ConfigurationController@store')->name('configuration_store');
    Route::get('configuration/tables', 'ConfigurationController@tables');
    Route::get('configuration/records', 'ConfigurationController@records');

    // Companies Management
    Route::get('/companies', 'CompanyController@index')->name('companies_index');
    Route::get('/companies/records', 'CompanyController@records');
    Route::get('/companies/tables', 'CompanyController@tables');
    Route::get('/companies/{id}', 'CompanyController@show')->where('id', '[0-9]+');
    Route::get('/companies/{id}/data', 'CompanyController@getCompanyData')->where('id', '[0-9]+');
    Route::get('/companies/{id}/edit-form', 'CompanyController@editForm')->where('id', '[0-9]+');
    Route::put('/companies/{id}', 'CompanyController@update')->where('id', '[0-9]+');
    Route::delete('/companies/{id}', 'CompanyController@destroy')->where('id', '[0-9]+');
    Route::post('/companies/{id}/environment', 'CompanyController@changeEnvironment')->where('id', '[0-9]+');
    Route::post('/companies/{id}/toggle-state', 'CompanyController@toggleState')->where('id', '[0-9]+');
    Route::post('/companies/{id}/software', 'CompanyController@updateSoftware')->where('id', '[0-9]+');
    Route::post('/companies/{id}/certificate', 'CompanyController@uploadCertificate')->where('id', '[0-9]+');
    Route::get('/companies/{id}/resolutions-list', 'CompanyController@resolutionsList')->where('id', '[0-9]+');
    Route::post('/companies/{id}/resolution', 'CompanyController@createResolution')->where('id', '[0-9]+');
    Route::get('/companies/{id}/documents', 'CompanyController@getDocuments')->where('id', '[0-9]+');
    Route::put('/companies/resolution/{id}', 'CompanyController@updateResolution');
    Route::delete('/companies/resolution/{id}', 'CompanyController@deleteResolution');

    Route::get('tax', 'TaxController@index')->name('tax_index');
    Route::get('tax/records', 'TaxController@records');

    Route::get('documents', 'DocumentController@index')->name('documents_index');
    Route::get('documents/records', 'DocumentController@records');
    Route::get('documents/downloadxml/{xml}', 'DocumentController@downloadxml');
    Route::get('documents/downloadpdf/{pdf}', 'DocumentController@downloadpdf');
    Route::post('documents/resend/{id}', 'DocumentController@resend')->name('documents_resend');

});

Route::get('qr', 'QrController@generateQr');
Route::get('pdf','ListingController@generatePDF');
Route::get('/listings', 'ListingController@index');

Route::get('invoice/xml/{filename}', function($fisicroute)
{
    $path = storage_path($fisicroute);
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/xml'
    ]);
});

Route::get('invoice/pdf/{filename}', function($fisicroute)
{
    $path = storage_path("facturas/pdf/".$fisicroute);
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/pdf'
    ]);
});

//mail test
Route::get('laravel-send-email', 'EmailController@sendEMail');
















