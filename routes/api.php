<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// api customer
Route::post('registerCustomer',[\App\Http\Controllers\CustomerController::class, 'registerCustomer']);
Route::post('loginCustomer',[\App\Http\Controllers\CustomerController::class, 'loginCustomer']);
Route::post('newloginCustomer',[\App\Http\Controllers\CustomerController::class, 'newloginCustomer']);
Route::post('newRegisterCustomer',[\App\Http\Controllers\CustomerController::class, 'newRegisterCustomer']);
Route::post('loginCustomerLevel1',[\App\Http\Controllers\CustomerController::class, 'loginCustomerLevel1']);
Route::post('loginCustomerLevel2',[\App\Http\Controllers\CustomerController::class, 'loginCustomerLevel2']);
Route::get('loginCustomer',[\App\Http\Controllers\CustomerController::class, 'loginCustomer']);
Route::post('codeConfirmAuth',[\App\Http\Controllers\CustomerController::class, 'codeConfirmAuth']);
Route::post('registerCustomerWeb',[\App\Http\Controllers\CustomerController::class, 'regiterCustomerWeb']);
Route::post('infoCustomer',[\App\Http\Controllers\CustomerController::class, 'infoCustomer']);
Route::get('addSolde',[\App\Http\Controllers\CustomerController::class, 'addSolde']);
Route::post('searchCustomer',[\App\Http\Controllers\CustomerController::class, 'searchCustomer']);// recherché un client
Route::post('recupPass',[\App\Http\Controllers\CustomerController::class, 'recupPass']);
Route::post('logOut',[\App\Http\Controllers\CustomerController::class, 'logOut']);//déconnexion
Route::post('getInfoNew',[\App\Http\Controllers\CustomerController::class, 'getInfoNew']);//vérifiersi c'est un nouveau
Route::post('getInfoNewApp',[\App\Http\Controllers\CustomerController::class, 'getInfoNewApp']);//vérifiersi c'est un nouveau pour la derniere mise à jour
Route::post('updatedCustomerinfo',[\App\Http\Controllers\CustomerController::class, 'updatedCustomerinfo']);//Mettre à jours les info
Route::get('recupIdCustomer',[\App\Http\Controllers\CustomerController::class, 'recupIdCustomer']);//Mettre à jours les info
Route::get('recupIdCustomer20',[\App\Http\Controllers\CustomerController::class, 'recupIdCustomer20']);//Mettre à jours les info
Route::post('beComeCom',[\App\Http\Controllers\CustomerController::class, 'beComeCom']);//devenir un commercial
Route::get('recupProgram',[\App\Http\Controllers\CustomerController::class, 'recupProgram']);
Route::post('contactCustomer',[\App\Http\Controllers\CustomerController::class, 'contactCustomer']);// Contacter les clients du challenge.
Route::post('updatedWhatsApp',[\App\Http\Controllers\CustomerController::class, 'updatedWhatsApp']);// updated whatsapp customer.
Route::post('sendSondage',[\App\Http\Controllers\CustomerController::class, 'sendSondage']);// Sondage récclamations

Route::get('compteVues',[\App\Http\Controllers\CustomerController::class, 'compteVues']);// Compter les vues
Route::post('compteVues',[\App\Http\Controllers\CustomerController::class, 'compteVues']);// Compter les vues

Route::get('compteVuesNew',[\App\Http\Controllers\CustomerController::class, 'compteVuesNew']);// Compter les vues
Route::post('compteVuesNew',[\App\Http\Controllers\CustomerController::class, 'compteVuesNew']);// Compter les vues

Route::post('remove',[\App\Http\Controllers\CustomerController::class, 'remove']);// supprimer compte
Route::post('recupcomte',[\App\Http\Controllers\CustomerController::class, 'recupcomte']);// Recup compte à supprimer
Route::post('resendSms',[\App\Http\Controllers\CustomerController::class, 'resendSms']);// renvoyer le code de securité

Route::get('getInfoTransfert',[\App\Http\Controllers\CustomerController::class, 'getInfoTransfert']);// Info numero transfert
Route::post('getInfoTransfert',[\App\Http\Controllers\CustomerController::class, 'getInfoTransfert']);// Info numero transfert
Route::post('AddInfoPass',[\App\Http\Controllers\CustomerController::class, 'AddInfoPass']);// Ajouter les infortion recup password

Route::get('countScoreCustomer',[\App\Http\Controllers\CustomerController::class, 'countScoreCustomer']);// Compte score customer
Route::get('countScoreCustomerUssd',[\App\Http\Controllers\CustomerController::class, 'countScoreCustomerUssd']);// Compte score customer ussd
Route::get('countScoreCustomerUssdRecup',[\App\Http\Controllers\CustomerController::class, 'countScoreCustomerUssdRecup']);// Compte score Recup customer ussd
Route::get('countScoreCustomerEdan',[\App\Http\Controllers\CustomerController::class, 'countScoreCustomerEdan']);// Compte score customer edan

Route::post('getInfoCustomerSuper',[\App\Http\Controllers\CustomerController::class, 'getInfoCustomerSuper']);// Search customer trans supervision

Route::get('getInfocustomerComplement',[\App\Http\Controllers\CustomerController::class, 'getInfocustomerComplement']);// Add info device and region customer

Route::post('addAgentGam',[\App\Http\Controllers\CustomerController::class, 'addAgentGam']);// Add agent  getAgent
Route::post('getAgent',[\App\Http\Controllers\CustomerController::class, 'getAgent']);// getAgent

Route::post('confirmeDemandeSolde',[\App\Http\Controllers\CustomerController::class, 'confirmeDemandeSolde']);// conforme trans demande

Route::post('retireAgent',[\App\Http\Controllers\CustomerController::class, 'retireAgent']);// Retirer un agent
Route::post('addDataAnalyser',[\App\Http\Controllers\CustomerController::class, 'addDataAnalyser']);// Add info device and region customer

Route::post('recupRechargeSolde',[\App\Http\Controllers\CustomerController::class, 'recupRechargeSolde']);// get trans recharge solde
Route::post('addFilleulMyPay',[\App\Http\Controllers\CustomerController::class, 'addFilleulMyPay']);// Add filleul in MyPay

Route::get('sendWhatsAppMessageGet',[\App\Http\Controllers\CustomerController::class, 'sendWhatsAppMessageGet']);

Route::get('contactVisaValideTrans',[\App\Http\Controllers\CustomerController::class, 'contactVisaValideTrans']);
Route::get('PointFinAnnee',[\App\Http\Controllers\CustomerController::class, 'PointFinAnnee']);
Route::get('statUSSDandNewClient',[\App\Http\Controllers\CustomerController::class,'statUSSDandNewClient']);
Route::post('getPriority',[\App\Http\Controllers\CustomerController::class, 'getPriority']);


Route::post('searchOrCreateCustomer',[\App\Http\Controllers\CustomerController::class, 'searchOrCreateCustomer']);
Route::post('getCodeLogin',[\App\Http\Controllers\CustomerController::class, 'getCodeLogin']);
Route::post('valideConnexion',[\App\Http\Controllers\CustomerController::class, 'valideConnexion']);
Route::post('valideConnexionApp',[\App\Http\Controllers\CustomerController::class, 'valideConnexionApp']);

Route::post('commandeGampay',[\App\Http\Controllers\CustomerController::class, 'commandeGampay']);
Route::post('recupFondPartner',[\App\Http\Controllers\CustomerController::class, 'recupFondPartner']);
Route::get('getlinkStoreForInstallation',[\App\Http\Controllers\CustomerController::class, 'getlinkStoreForInstallation']);
Route::post('testTemplateInstallation',[\App\Http\Controllers\CustomerController::class, 'testTemplateInstallation']);



// cartes GAM
Route::post('loginCard',[\App\Http\Controllers\CustomerController::class,'loginCard']);
Route::post('affectationCard',[\App\Http\Controllers\CustomerController::class, 'affectationCard']);
Route::post('statusCard',[\App\Http\Controllers\CustomerController::class, 'statusCard']);

// learning Menus and Payment
Route::post('getMenu',[\App\Http\Controllers\CustomerController::class, 'getMenu']);
Route::post('getPayment',[\App\Http\Controllers\CustomerController::class, 'getPayment']);
Route::post('setMenu',[\App\Http\Controllers\CustomerController::class, 'setMenu']);
Route::post('setPayment',[\App\Http\Controllers\CustomerController::class, 'setPayment']);

Route::post('testSendNewTemplateUssd',[\App\Http\Controllers\CustomerController::class, 'testSendNewTemplateUssd']);

Route::post('testNewTemplateUssd',[\App\Http\Controllers\CustomerController::class, 'testNewTemplateUssd']);

// api historique transaction
Route::post('historyTransCustomer',[\App\Http\Controllers\HistoriquetransController::class, 'customerHistoryTrans']);
Route::post('newHistoryTransCustomer',[\App\Http\Controllers\HistoriquetransController::class, 'newHistoriqueTrans']);
Route::get('newHistoriqueTransReplication',[\App\Http\Controllers\HistoriquetransController::class, 'newHistoriqueTransReplication']);
Route::post('newHistoryTransCustomerGam',[\App\Http\Controllers\HistoriquetransController::class, 'newHistoriqueTransGam']);
Route::post('newHistoriqueTransGab',[\App\Http\Controllers\HistoriquetransController::class, 'newHistoriqueTransGab']);

Route::get('getTokenTestApp',[\App\Http\Controllers\HistoriquetransController::class, 'getTokenTestApp']);

Route::get('getNomMobile',[\App\Http\Controllers\HistoriquetransController::class, 'getNomMobile']); // recupérer les noms des mobile money

Route::post('updateIdIwomy',[\App\Http\Controllers\HistoriquetransController::class, 'updateIdIwomy']);
Route::get('getStatut',[\App\Http\Controllers\HistoriquetransController::class, 'getStatut']);// vérifier l'état de la transaction iwomy
Route::post('getTokenIwomi',[\App\Http\Controllers\HistoriquetransController::class, 'getTokenIwomi']);// vérifier l'état de la transaction iwomy
Route::post('getTrans',[\App\Http\Controllers\HistoriquetransController::class, 'getTrans']);// RECHERGER UNE TRANSACTION PEA
Route::post('editTrans',[\App\Http\Controllers\HistoriquetransController::class, 'editTrans']);// MODIFIER UNE TRANSACTION PEA
Route::post('getTransQr',[\App\Http\Controllers\HistoriquetransController::class, 'getTransQr']);//Récupérer les transaction Qr
Route::post('addPieceCustomer',[\App\Http\Controllers\HistoriquetransController::class, 'addPieceCustomer']);//Récupérer les transaction Qr
Route::post('callBackAM',[\App\Http\Controllers\HistoriquetransController::class, 'callBackAM']);//Airtel callBack
Route::post('restartTrans',[\App\Http\Controllers\HistoriquetransController::class, 'restartTrans']);//Relancer les transactions des partenaires
Route::post('rembourseVisa',[\App\Http\Controllers\HistoriquetransController::class, 'rembourserClientVisa']);//Relancer les transactions des partenaires
Route::get('remEdan',[\App\Http\Controllers\HistoriquetransController::class, 'remEdan']);//Relancer les transactions des partenaires
Route::post('rechargeGab',[\App\Http\Controllers\HistoriquetransController::class, 'rechargeGab']);//Recharge by gab
Route::post('storeGabStock',[\App\Http\Controllers\HistoriquetransController::class, 'storeGabStock']);//stock gab customer
Route::post('cleanGabStock',[\App\Http\Controllers\HistoriquetransController::class, 'cleanGabStock']);// remove stock gab customer

Route::get('orabankGetAcces',[\App\Http\Controllers\HistoriquetransController::class, 'orabankGetAcces']);//token orabank
Route::post('orderOrabankTest',[\App\Http\Controllers\HistoriquetransController::class, 'orderOrabankTest']);//token orabank
Route::get('valideVisaOperation',[\App\Http\Controllers\HistoriquetransController::class, 'valideVisaOperation']);//  TRANS BY VISA ORABANK


Route::get('recupTransVisa',[\App\Http\Controllers\HistoriquetransController::class, 'recupTransVisa']);//service
Route::post('transPartenaire',[\App\Http\Controllers\HistoriquetransController::class, 'transPartenaire']);//suivi partenaires
Route::post('recupGain',[\App\Http\Controllers\HistoriquetransController::class, 'recupGain']);

Route::post('rechargeCard',[\App\Http\Controllers\HistoriquetransController::class, 'rechargeCard']);
Route::post('allTransBfm',[\App\Http\Controllers\HistoriquetransController::class, 'allTransBfm']);// recup bfm
Route::post('getMyBfm',[\App\Http\Controllers\HistoriquetransController::class, 'getMyBfm']);// recup bfm
Route::post('updateRia',[\App\Http\Controllers\HistoriquetransController::class,'updateRia']);
Route::post('createTransCadeau',[\App\Http\Controllers\HistoriquetransController::class, 'createTransCadeau']);//creer la transaction cadeau
Route::post('getStatusTrans',[\App\Http\Controllers\HistoriquetransController::class, 'getStatusTrans']);// recup bfm


// rendu monnaie via whatsapp
Route::post('getTransRmWhatapp',[\App\Http\Controllers\HistoriquetransController::class, 'getTransRmWhatapp']);
Route::post('getTransRmWhatAppOk',[\App\Http\Controllers\HistoriquetransController::class, 'getTransRmWhatAppOk']);
Route::get('verifWhatsAppTransTraite',[\App\Http\Controllers\HistoriquetransController::class, 'verifWhatsAppTransTraite']);// Verif status message whatsApp
Route::get('testsendTemplateRm',[\App\Http\Controllers\HistoriquetransController::class, 'testsendTemplateRm']);

Route::post('testNewTemplateRm',[\App\Http\Controllers\HistoriquetransController::class, 'testNewTemplateRm']);
Route::get('sendTemplateAfterRm',[\App\Http\Controllers\HistoriquetransController::class, 'sendTemplateAfterRm']);



/********** Rapport rendu monnaie *********************/
Route::get('getRapportRm',[\App\Http\Controllers\ServicesController::class, 'getRapportRm']);
Route::post('getRapportRm',[\App\Http\Controllers\ServicesController::class, 'getRapportRm']);


// api notifications
Route::post('checkNotification',[\App\Http\Controllers\HistoriquetransController::class, 'mesNotifications']);
Route::post('createNotification',[\App\Http\Controllers\HistoriquetransController::class, 'createNotification']);
Route::post('getNotification',[\App\Http\Controllers\HistoriquetransController::class, 'getNotification']);
Route::post('deleteNotification',[\App\Http\Controllers\HistoriquetransController::class, 'deleteNotification']);
Route::post('sendNotification',[\App\Http\Controllers\HistoriquetransController::class, 'sendNotification']);
Route::get('sendNotificationget',[\App\Http\Controllers\HistoriquetransController::class, 'sendNotification']);
Route::get('cleanNotification',[\App\Http\Controllers\HistoriquetransController::class, 'cleanNotification']);
Route::get('cleanForceNotification',[\App\Http\Controllers\HistoriquetransController::class, 'cleanForceNotification']);
Route::post('rembourser',[\App\Http\Controllers\HistoriquetransController::class, 'rembourser']);
Route::get('notifVisaAndReclam',[\App\Http\Controllers\HistoriquetransController::class, 'notifVisaAndReclam']);//Notification visa  && reclammation


Route::post('getNotifNotRead',[\App\Http\Controllers\CustomerController::class, 'getNotifNotRead']);
Route::post('readNotif',[\App\Http\Controllers\CustomerController::class, 'readNotif']);

// recupérer la version
Route::get('getAppVersion',[\App\Http\Controllers\CustomerController::class, 'getAppVersion']);

// cartes visa
Route::post('editCarte',[\App\Http\Controllers\CarteController::class, 'editCarte']);
Route::post('addCarte',[\App\Http\Controllers\CarteController::class, 'addCard']);
Route::get('addCarte',[\App\Http\Controllers\CarteController::class, 'addCard']);
Route::post('recupCarte',[\App\Http\Controllers\CarteController::class, 'recupCarte']);
Route::post('recupCarteScyd',[\App\Http\Controllers\CarteController::class, 'recupCarteScyd']);
Route::post('validePaiement',[\App\Http\Controllers\CarteController::class, 'validePaiement']);
Route::post('deleteCarte',[\App\Http\Controllers\CarteController::class, 'deleteCarte']);
Route::get('commandeCard',[\App\Http\Controllers\CarteController::class, 'commandeCard']);
Route::post('addSim',[\App\Http\Controllers\CarteController::class, 'addSim']);// Ajouter une sim internationale
Route::post('enableMySim',[\App\Http\Controllers\CarteController::class, 'enableMySim']);// Activer une sim internationale

//API COMPTEUR

Route::post('compteur',[\App\Http\Controllers\GamElectriciteHistController::class, 'compteur']);
Route::post('edan',[\App\Http\Controllers\GamElectriciteHistController::class, 'ticketEdan']);

Route::post('modifcompteur',[\App\Http\Controllers\GamElectriciteHistController::class, 'modifCompteur']);
Route::post('modifticket',[\App\Http\Controllers\GamElectriciteHistController::class, 'modifTicket']);
Route::post('deleteticket',[\App\Http\Controllers\GamElectriciteHistController::class, 'DeleteTicket']);
Route::post('deletecompteur',[\App\Http\Controllers\GamElectriciteHistController::class, 'DeleteCompteur']);

Route::post('recupCompteur',[\App\Http\Controllers\GamElectriciteHistController::class, 'recupCompteur']);
Route::post('recupTicket',[\App\Http\Controllers\GamElectriciteHistController::class, 'recupTicket']);
Route::post('transEdan',[\App\Http\Controllers\GamElectriciteHistController::class, 'transEdan']);

Route::get('testAddCustomerInfoData',[\App\Http\Controllers\HistoriquetransController::class, 'testAddCustomerInfoData']);



// APIS ORABANK
Route::post('visaOrabank',[\App\Http\Controllers\HistoriquetransController::class, 'visaOrabank']);// TEST GET TOKEN API ORABNAK
Route::post('orabankGetAcces',[\App\Http\Controllers\HistoriquetransController::class, 'orabankGetAcces']);//  GET TOKEN API ORABNAK
Route::get('orabankTransStatus',[\App\Http\Controllers\HistoriquetransController::class, 'orabankTransStatus']);//  GET STATUS TRANS
Route::get('orderStatusOrabank',[\App\Http\Controllers\HistoriquetransController::class, 'orderStatusOrabank']);//  GET ORDERS STATUS TRANS
Route::get('orderOrabank',[\App\Http\Controllers\HistoriquetransController::class, 'orderOrabank']);//  GET ORDERS STATUS TRANS
Route::post('transByVisaOrabank',[\App\Http\Controllers\HistoriquetransController::class, 'transByVisaOrabank']);//  TRANS BY VISA ORABANK



Route::post('notif',[App\Http\Controllers\NotificationController::class, 'notification']);


//*****  Services routres *******
Route::post('sendSms',[\App\Http\Controllers\ServicesController::class, 'sendSms']);
Route::get('sendSmsGet',[\App\Http\Controllers\ServicesController::class, 'sendSms']);
Route::post('sendForfait',[\App\Http\Controllers\HistoriquetransController::class, 'sendForfait']);
Route::post('sendForfaitCadeau',[\App\Http\Controllers\HistoriquetransController::class, 'sendForfaitCadeau']);// envoyer forfait cadeau
Route::get('mailPass',[\App\Http\Controllers\ServicesController::class, 'mailPass']);
//Route::post('recupTransApis',[\App\Http\Controllers\ServicesController::class, 'recupTransApis']);
Route::get('updatesoldes',[\App\Http\Controllers\ServicesController::class, 'updatesoldes']);
Route::get('iaCustomerUssd',[\App\Http\Controllers\ServicesController::class, 'iaCustomerUssd']);
Route::get('callBackPartnaire',[\App\Http\Controllers\ServicesController::class, 'callBackPartnaire']);
Route::post('callBackPartnaire',[\App\Http\Controllers\ServicesController::class, 'callBackPartnaire']);

Route::get('valideTransApisPartenaire',[\App\Http\Controllers\ServicesController::class, 'valideTransApisPartenaire']);
Route::post('recupTransApis',[\App\Http\Controllers\ServicesController::class, 'recupTransApis']);
Route::post('insertFailAttempt',[\App\Http\Controllers\ServicesController::class, 'insertFailAttempt']);
Route::get('storeTokenOrabank',[\App\Http\Controllers\ServicesController::class, 'storeTokenOrabank']);// créer un token
Route::post('storePaymentMode',[\App\Http\Controllers\ServicesController::class, 'storePaymentMode']);// Add Paiement Mode
Route::post('storeErrorApp',[\App\Http\Controllers\ServicesController::class, 'storeErrorApp']);// Save error app
Route::get('disableOrabank',[\App\Http\Controllers\ServicesController::class, 'disableOrabank']);// Disable Orabank card

Route::post('recupAndDisableCustomerParrainage',[\App\Http\Controllers\ServicesController::class, 'recupAndDisableCustomerParrainage']);
Route::post('recupAndAccumulationGainParrainage',[\App\Http\Controllers\ServicesController::class, 'recupAndAccumulationGainParrainage']);
Route::post('recupAndAccumulationGainParrainageOnly',[\App\Http\Controllers\ServicesController::class, 'recupAndAccumulationGainParrainageOnly']);

Route::get('ussdEl',[\App\Http\Controllers\ServicesController::class, 'ussdEl']);

Route::get('prisecondsup',[\App\Http\Controllers\ServicesController::class,'prisecondsup']);// segmentation clients


Route::post('errorsApp',[\App\Http\Controllers\ServicesController::class,'errorsApp']);// segmentation clients

Route::get('telechargemntappclient',[\App\Http\Controllers\ServicesController::class, 'telechargemntappclient']);

Route::post('imagepub',[\App\Http\Controllers\ServicesController::class,'imagepub']);// Image pub

Route::post('exchangeDeviceXAf',[\App\Http\Controllers\ServicesController::class,'exchangeDeviceXAf']);
Route::get('sendMessageNewCustomer',[\App\Http\Controllers\ServicesController::class, 'sendMessageNewCustomer']);
Route::post('parameters',[\App\Http\Controllers\ServicesController::class,'parameters']);
Route::get('confrimTransTraitStatus',[\App\Http\Controllers\ServicesController::class,'confrimTransTraitStatus']);
Route::get('confrimTransDemandeStatus',[\App\Http\Controllers\ServicesController::class, 'confrimTransDemandeStatus']);
Route::get('clientsActifDodo',[\App\Http\Controllers\ServicesController::class,'clientsActifDodo']);
Route::post('clientsActifDodo',[\App\Http\Controllers\ServicesController::class,'clientsActifDodo']);

Route::get('migratedCadeau',[\App\Http\Controllers\ServicesController::class,'migratedCadeau']);
Route::get('buyforme',[\App\Http\Controllers\ServicesController::class, 'buyforme']);

Route::get('distributionApp',[\App\Http\Controllers\ServicesController::class, 'distributionApp']);
Route::get('distributionUssd',[\App\Http\Controllers\ServicesController::class, 'distributionUssd']);
Route::get('distributionAppCarte',[\App\Http\Controllers\ServicesController::class, 'distributionAppCarte']);

Route::get('sendMessageNewCustomerCreditUssd',[\App\Http\Controllers\ServicesController::class, 'sendMessageNewCustomerCreditUssd']);

Route::get('getStatsInternational',[\App\Http\Controllers\ServicesController::class, 'getStatsInternational']);
Route::get('notifSoldeTrans',[\App\Http\Controllers\ServicesController::class, 'notifSoldeTrans']);
Route::get('notifTransMoov',[\App\Http\Controllers\ServicesController::class, 'notifTransMoov']);

Route::get('checkNumberUseWhatsapp',[\App\Http\Controllers\ServicesController::class, 'checkNumberUseWhatsapp']);
Route::post('recupCallBack',[\App\Http\Controllers\ServicesController::class, 'recupCallBack']);
Route::post('recupCallBackService',[\App\Http\Controllers\ServicesController::class, 'recupCallBackService']);
Route::post('recupCallBackMyPay',[\App\Http\Controllers\ServicesController::class, 'recupCallBackMyPay']);
Route::post('sendTemplateMessage',[\App\Http\Controllers\ServicesController::class, 'sendTemplateMessage']);
Route::post('sendTemplateMessageTest',[\App\Http\Controllers\ServicesController::class, 'sendTemplateMessageTest']);
Route::get('verifWhatsAppMessagebackup',[\App\Http\Controllers\ServicesController::class, 'verifWhatsAppMessagebackup']);
Route::get('orientationScydCustomer',[\App\Http\Controllers\ServicesController::class, 'orientationScydCustomer']);
Route::get('confirmeRecupTransScyd',[\App\Http\Controllers\ServicesController::class, 'confirmeRecupTransScyd']);

Route::get('sendWhatsapCustmerSuivi',[\App\Http\Controllers\ServicesController::class, 'sendWhatsapCustmerSuivi']);
Route::get('getCumulCollecteur',[\App\Http\Controllers\ServicesController::class, 'getCumulCollecteur']);


// rm repay
Route::get('whatsappRmRepay', [\App\Http\Controllers\ServicesController::class, 'whatsappRmRepay']);
Route::get('rembourseRmwhatsappLoading', [\App\Http\Controllers\ServicesController::class, 'rembourseRmwhatsappLoading']);
Route::get('rmWhatsappHelpCustomer',[\App\Http\Controllers\ServicesController::class, 'rmWhatsappHelpCustomer']);

// IP script
Route::get('getContryIp',[\App\Http\Controllers\ServicesController::class, 'getContryIp']);
Route::get('getContryIpTest',[\App\Http\Controllers\HistoriquetransController::class, 'getContryIpTest']);

// alertes script
Route::get('getAlerteTransactions',[\App\Http\Controllers\ServicesController::class, 'getAlerteTransactions']);
Route::get('getAlerteRechargeVisa',[\App\Http\Controllers\ServicesController::class, 'getAlerteRechargeVisa']);
Route::get('testNotifGroup', [\App\Http\Controllers\ServicesController::class, 'testNotifGroup']);
Route::get('getAlertePaiementPartenaire', [\App\Http\Controllers\ServicesController::class, 'getAlertePaiementPartenaire']);
Route::get('getAlerteReclamations', [\App\Http\Controllers\ServicesController::class, 'getAlerteReclamations']);
Route::get('getAlerteReclamations2', [\App\Http\Controllers\ServicesController::class, 'getAlerteReclamations2']);



Route::get('testVerifWhatsapp',[\App\Http\Controllers\HistoriquetransController::class, 'testVerifWhatsapp']);
Route::get('assistCustomerChallenge',[\App\Http\Controllers\ServicesController::class, 'assistCustomerChallenge']);


Route::post('sendTemplateAssist1xbet',[\App\Http\Controllers\ServicesController::class, 'sendTemplateAssist1xbet']);

Route::get('getTransTestInterne',[\App\Http\Controllers\ServicesController::class, 'getTransTestInterne']);
Route::post('getTransTestInterne',[\App\Http\Controllers\ServicesController::class, 'getTransTestInterne']);

Route::get('getCustomerAction',[\App\Http\Controllers\ServicesController::class, 'getCustomerAction']);
Route::post('getCustomerAction',[\App\Http\Controllers\ServicesController::class, 'getCustomerAction']);

Route::get('getForfaitDataCredit',[\App\Http\Controllers\ServicesController::class, 'getForfaitDataCredit']);
Route::post('getForfaitDataCredit',[\App\Http\Controllers\ServicesController::class, 'getForfaitDataCredit']);

Route::get('callBackAMOfflineAppRequest',[\App\Http\Controllers\ServicesController::class, 'callBackAMOfflineAppRequest']);
Route::post('callBackAMOfflineAppRequest',[\App\Http\Controllers\ServicesController::class, 'callBackAMOfflineAppRequest']);




//************* reclamation apis *****************
Route::post('getReclamation',[\App\Http\Controllers\CustomerController::class, 'getReclamation']);
Route::post('storReclamaion',[\App\Http\Controllers\CustomerController::class, 'storReclamaion']);
Route::post('likeReclamation',[\App\Http\Controllers\CustomerController::class, 'likeReclamation']);
Route::post('counts',[\App\Http\Controllers\ReclamationController::class,'countReclamations']);

//************* app supervision apis *****************
Route::post('getDataSupervision',[\App\Http\Controllers\HistoriquetransController::class, 'getDataSupervision']);

//************* Forfait International apis *****************
Route::get('verifTransInternational',[\App\Http\Controllers\HistoriquetransController::class, 'verifTransInternational']);
Route::post('renameData',[\App\Http\Controllers\HistoriquetransController::class, 'renameDataInternational']);
Route::post('esimInventor',[\App\Http\Controllers\HistoriquetransController::class, 'esimInventor']);
Route::post('accessData',[\App\Http\Controllers\HistoriquetransController::class, 'esimAccessToken']);
Route::get('testSendNotif',[\App\Http\Controllers\HistoriquetransController::class, 'testSendNotif']);
Route::post('getDataForfait',[\App\Http\Controllers\CustomerController::class, 'getDataForfait']);

/********************************************************************************** ChallengeContoller Controller route **************************************************************************************/

// challenge
Route::get('verifieFilleu',[\App\Http\Controllers\ChallengeContoller::class, 'verifieFilleu']);
Route::get('getCommisions',[\App\Http\Controllers\ChallengeContoller::class, 'getCommisions']);
Route::post('becomStudent',[\App\Http\Controllers\ChallengeContoller::class, 'becomStudent']);
Route::post('removeStudent',[\App\Http\Controllers\ChallengeContoller::class, 'removeStudent']);
Route::get('compteVueAuto',[\App\Http\Controllers\ChallengeContoller::class, 'compteVueAuto']);
Route::get('getTransCaisse',[\App\Http\Controllers\ChallengeContoller::class, 'getTransCaisse']);
Route::get('recupClientsHotesses',[\App\Http\Controllers\ChallengeContoller::class, 'recupClientsHotesses']);//recupérer les clients pour les hotesse
Route::get('removeCodeConfirmPrixImport',[\App\Http\Controllers\ChallengeContoller::class, 'removeCodeConfirmPrixImport']); // Vider le code_confirm des inactif

Route::get('getCustomerPrixImportSecondChallenge',[\App\Http\Controllers\ChallengeContoller::class, 'getCustomerPrixImportSecondChallenge']);
Route::get('valideCustomerPrixImportSecondChallenge',[\App\Http\Controllers\ChallengeContoller::class, 'valideCustomerPrixImportSecondChallenge']);
Route::get('generateLink',[\App\Http\Controllers\ChallengeContoller::class, 'generateLink']);
Route::post('saveFollwers',[\App\Http\Controllers\ChallengeContoller::class, 'saveFollwers']);

Route::post('distributionCadeauAgent',[\App\Http\Controllers\ChallengeContoller::class, 'distributionCadeauAgent']);

Route::get('sharedLinkAmbassadeur',[\App\Http\Controllers\ChallengeContoller::class, 'sharedLinkAmbassadeur']);

Route::get('parrainageGamOs',[\App\Http\Controllers\ChallengeContoller::class, 'parrainageGamOs']);
Route::post('parrainageGamOs',[\App\Http\Controllers\ChallengeContoller::class, 'parrainageGamOs']);

Route::get('getInfoChallengeForCustomer',[\App\Http\Controllers\ChallengeContoller::class, 'getInfoChallengeForCustomer']);
Route::post('getInfoChallengeForCustomer',[\App\Http\Controllers\ChallengeContoller::class, 'getInfoChallengeForCustomer']);

Route::get('participationTombola',[\App\Http\Controllers\ChallengeContoller::class, 'participationTombola']);
Route::post('participationTombola',[\App\Http\Controllers\ChallengeContoller::class, 'participationTombola']);

Route::get('participationTombolaTest',[\App\Http\Controllers\ChallengeContoller::class, 'participationTombolaTest']);
Route::post('participationTombolaTest',[\App\Http\Controllers\ChallengeContoller::class, 'participationTombolaTest']);

Route::get('getParticipationTombola',[\App\Http\Controllers\ChallengeContoller::class, 'getParticipationTombola']);
Route::post('getParticipationTombola',[\App\Http\Controllers\ChallengeContoller::class, 'getParticipationTombola']);

Route::get('getLotChallenge',[\App\Http\Controllers\ChallengeContoller::class, 'getLotChallenge']);
Route::post('getLotChallenge',[\App\Http\Controllers\ChallengeContoller::class, 'getLotChallenge']);

/********************************************************************************** End ChallengeContoller Controller route **************************************************************************************/


// ****************** STATUS****************************
Route::post('getStatus',[\App\Http\Controllers\StatusController::class, 'getStatus']);
Route::post('status',[\App\Http\Controllers\StatusController::class, 'status']);
Route::post('getComment',[\App\Http\Controllers\StatusController::class, 'getComment']);
Route::post('getStatusUser',[\App\Http\Controllers\StatusController::class, 'getStatusUser']);
Route::post('addComment',[\App\Http\Controllers\StatusController::class, 'addComment']);
Route::post('storeStatut',[\App\Http\Controllers\StatusController::class, 'storeStatut']);
Route::post('updateStatut',[\App\Http\Controllers\StatusController::class, 'updateStatut']);
Route::post('getLocationPhone',[\App\Http\Controllers\StatusController::class, 'getLocationPhone']);
Route::get('paiement',[\App\Http\Controllers\StatusController::class, 'paiement']);
Route::post('getShare',[\App\Http\Controllers\StatusController::class, 'getShare']);
Route::post('getReferent',[\App\Http\Controllers\StatusController::class, 'getReferent']);


/**************************** Route Es Dubai**************************/
Route::post('GetPreInscrisptionDubai',[\App\Http\Controllers\ReclamationController::class,'GetPreInscrisptionDubai']);

Route::post('StorePreInscrisptionDubai',[\App\Http\Controllers\ReclamationController::class,'StorePreInscrisptionDubai']);


/******************** Api Soldes******************************/
Route::get('checkSoldes',[\App\Http\Controllers\SoldeContoller::class, 'checkSoldes']);
Route::post('rechargeSoldes',[\App\Http\Controllers\SoldeContoller::class, 'rechargeSoldes']);
Route::post('getInfoProduit',[\App\Http\Controllers\SoldeContoller::class, 'getInfoProduit']);
Route::post('getSoldes',[\App\Http\Controllers\SoldeContoller::class, 'getSoldes']);
Route::get('soldeMoov',[\App\Http\Controllers\SoldeContoller::class, 'soldeMoov']);
Route::get('sendNotifForMoovPayment',[\App\Http\Controllers\ServicesController::class, 'sendNotifForMoovPayment']);
Route::get('alerteSoldesOperations',[\App\Http\Controllers\SoldeContoller::class, 'alerteSoldesOperations']);




/******************** car **********************/
Route::post('storeCars',[\App\Http\Controllers\CarController::class,'storeCars']);
Route::post('getCars',[\App\Http\Controllers\CarController::class,'getCars']);
Route::get('cars',[\App\Http\Controllers\CarController::class, 'cars']);
Route::post('storeMarkCars',[\App\Http\Controllers\CarController::class,'storeMarkCars']);
Route::post('editCars',[\App\Http\Controllers\CarController::class,'editCars']);

/****************** carte de visite ***********/

Route::post('addCarteVisite',[\App\Http\Controllers\VisiteController::class, 'addcarte']);
Route::post('getVisiteCarte',[\App\Http\Controllers\VisiteController::class, 'getVisiteCarte']);
Route::post('saveCarte',[\App\Http\Controllers\VisiteController::class, 'saveCarte']);
Route::post('getPartageCustomer',[\App\Http\Controllers\VisiteController::class, 'getPartageCustomer']);


Route::post('getCustomerAddcontact',[\App\Http\Controllers\ServicesController::class, 'getCustomerAddcontact']);

/************** optionController route ***************/

Route::post('storeCustomerCommande',[\App\Http\Controllers\OptionController::class, 'storeCustomerCommande']);
Route::post('getOrSetCustomerCommande',[\App\Http\Controllers\OptionController::class, 'getOrSetCustomerCommande']);
Route::post('getVendeurCommande',[\App\Http\Controllers\OptionController::class, 'getVendeurCommande']);
Route::post('getOrSetCommande',[\App\Http\Controllers\OptionController::class, 'getOrSetCommande']);
Route::post('getCustomerCommande',[\App\Http\Controllers\OptionController::class, 'getCustomerCommande']);
Route::post('getCountCustomerOrCommande',[\App\Http\Controllers\OptionController::class, 'getCountCustomerOrCommande']);
Route::post('recupCommande',[\App\Http\Controllers\OptionController::class, 'recupCommande']);
Route::get('paymentCommande',[\App\Http\Controllers\OptionController::class, 'paymentCommande']);
Route::post('paymentCommande',[\App\Http\Controllers\OptionController::class, 'paymentCommande']);


/************** Route for ScriptController ***************/


/****** wifi *******/
Route::post('validetranswifi',[\App\Http\Controllers\ScriptController::class, 'validetranswifi']);
Route::get('validetranswifi',[\App\Http\Controllers\ScriptController::class, 'validetranswifi']);
Route::get('createTransWifi',[\App\Http\Controllers\ScriptController::class, 'createTransWifi']);

Route::get('solveOperationRechargeVisa',[\App\Http\Controllers\ScriptController::class, 'solveOperationRechargeVisa']);
Route::get('solveOperationRechargeVisa',[\App\Http\Controllers\ScriptController::class, 'solveOperationRechargeVisa']);
/****** Fin trans wifi *******/


/****** Customer Sleeping with pay *******/
Route::get('sendTemplateForCustomerUssdSleepingWithPay',[\App\Http\Controllers\ScriptController::class, 'sendTemplateForCustomerUssdSleepingWithPay']);
/****** Fin Customer Sleeping with pay *******/

/****** script remboursement Customer Sleeping with pay ussd *******/
Route::post('remboursementOfFraisForCustomerSleepingWithPay',[\App\Http\Controllers\ScriptController::class, 'remboursementOfFraisForCustomerSleepingWithPay']);
Route::get('remboursementOfFraisForCustomerSleepingWithPay',[\App\Http\Controllers\ScriptController::class, 'remboursementOfFraisForCustomerSleepingWithPay']);
/****** Fin script remboursement Customer Sleeping with pay ussd *******/

/****** Alerte nouveau client ussd *******/
Route::get('sendAlertNewCustomerUssd',[\App\Http\Controllers\ScriptController::class, 'sendAlertNewCustomerUssd']);
/****** Fin alerte nouveau client ussd *******/

/****** Lancement communication ussd *******/
Route::post('sendAlertEnchere',[\App\Http\Controllers\ScriptController::class, 'sendAlertEnchere']);
Route::get('sendAlertEnchere',[\App\Http\Controllers\ScriptController::class, 'sendAlertEnchere']);
/****** Fin Lancement communication ussd *******/


/*************** route gamview ***************/
Route::get('sendWhatsappGamView',[\App\Http\Controllers\CustomerController::class, 'sendWhatsappGamView']);
Route::post('sendWhatsappGamView',[\App\Http\Controllers\CustomerController::class, 'sendWhatsappGamView']);
Route::post('sendWhatsappGamViewShare',[\App\Http\Controllers\CustomerController::class, 'sendWhatsappGamViewShare']);
/************** Fin Route for ScriptController ***************/



/******************* GamPay Conversationnel ***********************/
Route::post('notifFirebaseHttp',[\App\Http\Controllers\GeneralController::class, 'notifFirebaseHttp']);
Route::get('notifFirebaseHttp',[\App\Http\Controllers\GeneralController::class, 'notifFirebaseHttp']);

/*Route::post('getUpdateLauncher',[\App\Http\Controllers\GeneralController::class, 'getUpdateLauncher']);
Route::get('getUpdateLauncher',[\App\Http\Controllers\GeneralController::class, 'getUpdateLauncher']);

/*************** Transaction APIS  **************/
Route::post('getHistoric',[\App\Http\Controllers\TransactionsController::class, 'getHistoric']);
Route::post('newTransaction',[\App\Http\Controllers\TransactionsController::class, 'newTransaction']);
Route::post('newTransactionVisa',[\App\Http\Controllers\TransactionsController::class, 'newTransactionVisa']);
Route::post('newTransactionGam',[\App\Http\Controllers\TransactionsController::class, 'newTransactionGam']);
Route::post('newTransactionRm',[\App\Http\Controllers\TransactionsController::class, 'newTransactionRm']);
Route::post('getTrans',[\App\Http\Controllers\TransactionsController::class, 'getTrans']);


Route::post('updateTransFirebase',[\App\Http\Controllers\TransactionsController::class, 'updateTransFirebase']);
Route::get('updateTransFirebase',[\App\Http\Controllers\TransactionsController::class, 'updateTransFirebase']);

Route::post('updateStatsForfait',[\App\Http\Controllers\TransactionsController::class, 'updateStatsForfait']);
Route::get('updateStatsForfait',[\App\Http\Controllers\TransactionsController::class, 'updateStatsForfait']);

Route::post('amPush',[\App\Http\Controllers\TransactionsController::class, 'amPush']);
Route::get('amPush',[\App\Http\Controllers\TransactionsController::class, 'amPush']);
Route::post('amPushInApp',[\App\Http\Controllers\TransactionsController::class, 'amPushInApp']);



//***************** Test Apis *****************///
Route::post('getInfoCustomerFlowMeta',[\App\Http\Controllers\CustomerController::class, 'getInfoCustomerFlowMeta']);

Route::post('getEntreprisesLocalInterantional',[\App\Http\Controllers\UsersController::class,'getEntreprisesLocalInterantional']);


/******************************* Route shareCom by scyd_Meta **************************************/
Route::get('createAccount',[\App\Http\Controllers\ServicesController::class, 'createAccount']);
Route::get('shareCom',[\App\Http\Controllers\ScriptController::class, 'shareCom']);
Route::post('shareCom',[\App\Http\Controllers\ScriptController::class, 'shareCom']);
/******************************* End route shareCom by scyd_Meta **************************************/

/*********************** Apis payment push **************************/
Route::get('getTokenPvit',[\App\Http\Controllers\PaymentController::class, 'getTokenPvit']);

Route::get('getPaymentPvit',[\App\Http\Controllers\PaymentController::class, 'getPaymentPvit']);
Route::post('getPaymentPvit',[\App\Http\Controllers\PaymentController::class, 'getPaymentPvit']);

Route::get('getInfoCustomerPvit',[\App\Http\Controllers\PaymentController::class, 'getInfoCustomerPvit']);
Route::post('getInfoCustomerPvit',[\App\Http\Controllers\PaymentController::class, 'getInfoCustomerPvit']);

Route::get('getStatusTransPvit',[\App\Http\Controllers\PaymentController::class, 'getStatusTransPvit']);
Route::post('getStatusTransPvit',[\App\Http\Controllers\PaymentController::class, 'getStatusTransPvit']);

Route::get('callBackPvit',[\App\Http\Controllers\PaymentController::class, 'callBackPvit']);
Route::post('callBackPvit',[\App\Http\Controllers\PaymentController::class, 'callBackPvit']);

Route::get('callBackAM',[\App\Http\Controllers\PaymentController::class, 'callBackAM']);
Route::post('callBackAM',[\App\Http\Controllers\PaymentController::class, 'callBackAM']);

Route::get('launchSummary',[\App\Http\Controllers\PaymentController::class, 'launchSummary']);
Route::post('launchSummary',[\App\Http\Controllers\PaymentController::class, 'launchSummary']);

Route::get('chargeWalletWithPush',[\App\Http\Controllers\PaymentController::class, 'chargeWalletWithPush']);
Route::post('chargeWalletWithPush',[\App\Http\Controllers\PaymentController::class, 'chargeWalletWithPush']);

Route::get('newCallBackAMOfflineAppRequest',[\App\Http\Controllers\PaymentController::class, 'newCallBackAMOfflineAppRequest']);
Route::post('newCallBackAMOfflineAppRequest',[\App\Http\Controllers\PaymentController::class, 'newCallBackAMOfflineAppRequest']);

Route::get('summaryPushAM',[\App\Http\Controllers\PaymentController::class, 'summaryPushAM']);

/**************************************** UserController Routes ********************************************************************/

Route::post('gcnewRegisterCustomer',[\App\Http\Controllers\UsersController::class, 'newRegisterCustomer']);
Route::post('gcloginCustomerLevel1',[\App\Http\Controllers\UsersController::class, 'loginCustomerLevel1']);
Route::post('gcloginCustomerLevel2',[\App\Http\Controllers\UsersController::class, 'loginCustomerLevel2']);
Route::post('getMarchandsActus',[\App\Http\Controllers\UsersController::class, 'getMarchandsActus']);

Route::post('editLastMessageCustomer',[\App\Http\Controllers\UsersController::class, 'editLastMessageCustomer']);
Route::get('editLastMessageCustomer',[\App\Http\Controllers\UsersController::class, 'editLastMessageCustomer']);

Route::post('parrainagePackInApp',[\App\Http\Controllers\UsersController::class, 'parrainagePackInApp']);
Route::get('parrainagePackInApp',[\App\Http\Controllers\UsersController::class, 'parrainagePackInApp']);



Route::post('getUserData',[\App\Http\Controllers\UsersController::class, 'getUserData']);
Route::post('editAccount',[\App\Http\Controllers\UsersController::class, 'editAccount']);

Route::post('getUsers',[\App\Http\Controllers\UsersController::class, 'getUsers']);

Route::post('updateUser',[\App\Http\Controllers\UsersController::class, 'updateUser']);

Route::get('registerConfigSims',[\App\Http\Controllers\UsersController::class, 'registerConfigSims']);
Route::post('registerConfigSims',[\App\Http\Controllers\UsersController::class, 'registerConfigSims']);

Route::get('getDataUssd',[\App\Http\Controllers\UsersController::class, 'getDataUssd']);
Route::post('getDataUssd',[\App\Http\Controllers\UsersController::class, 'getDataUssd']);

Route::get('linkGamPayUssd',[\App\Http\Controllers\UsersController::class, 'linkGamPayUssd']);
Route::post('linkGamPayUssd',[\App\Http\Controllers\UsersController::class, 'linkGamPayUssd']);

/**************************************** End UserController Routes ********************************************************************/

/********************************************************************************** CustomerWifiController Controller route **************************************************************************************/

Route::get('createdVoucher',[\App\Http\Controllers\CustomerWifiController::class, 'createdVoucher']);
Route::post('createdVoucher',[\App\Http\Controllers\CustomerWifiController::class, 'createdVoucher']);

/********************************************************************************** End CustomerWifiController Controller route **************************************************************************************/


/********************************************************************************** AgentController Controller route **************************************************************************************/
/************************* Agents routes ***************************/

Route::get('dataCreditAssistance',[\App\Http\Controllers\AgentController::class, 'dataCreditAssistance']);
Route::post('dataCreditAssistance',[\App\Http\Controllers\AgentController::class, 'dataCreditAssistance']);

Route::get('finalAssisteCustomer',[\App\Http\Controllers\AgentController::class, 'finalAssisteCustomer']);
Route::post('finalAssisteCustomer',[\App\Http\Controllers\AgentController::class, 'finalAssisteCustomer']);

Route::get('verifTrans',[\App\Http\Controllers\AgentController::class, 'verifTrans']);
Route::post('verifTrans',[\App\Http\Controllers\AgentController::class, 'verifTrans']);

Route::get('getWallet',[\App\Http\Controllers\AgentController::class, 'getWallet']);
Route::post('getWallet',[\App\Http\Controllers\AgentController::class, 'getWallet']);

Route::post('addWallet',[\App\Http\Controllers\AgentController::class, 'addWallet']);

Route::get('getDetailsForfait',[\App\Http\Controllers\AgentController::class, 'getDetailsForfait']);
Route::post('getDetailsForfait',[\App\Http\Controllers\AgentController::class, 'getDetailsForfait']);

Route::get('rechargeWallet',[\App\Http\Controllers\AgentController::class, 'rechargeWallet']);
Route::post('rechargeWallet',[\App\Http\Controllers\AgentController::class, 'rechargeWallet']);

Route::get('udateCallTimeOnline',[\App\Http\Controllers\AgentController::class, 'udateCallTimeOnline']);
Route::post('udateCallTimeOnline',[\App\Http\Controllers\AgentController::class, 'udateCallTimeOnline']);

Route::get('calculTimeCallUssd',[\App\Http\Controllers\AgentController::class, 'calculTimeCallUssd']);
Route::post('calculTimeCallUssd',[\App\Http\Controllers\AgentController::class, 'calculTimeCallUssd']);


Route::get('rechargeCallTimeOrData',[\App\Http\Controllers\AgentController::class, 'rechargeCallTimeOrData']);
Route::post('rechargeCallTimeOrData',[\App\Http\Controllers\AgentController::class, 'rechargeCallTimeOrData']);
/********************************************************************************** End AgentController Controller route **************************************************************************************/


/********************************************************************************** Progression Controller route **************************************************************************************/
Route::post('countProgression',[\App\Http\Controllers\ProgressionController::class, 'countProgression']);
Route::get('countProgression',[\App\Http\Controllers\ProgressionController::class, 'countProgression']);

Route::post('getProgression',[\App\Http\Controllers\ProgressionController::class, 'getProgression']);
Route::get('getProgression',[\App\Http\Controllers\ProgressionController::class, 'getProgression']);

Route::post('calculPointsStore',[\App\Http\Controllers\ProgressionController::class, 'calculPointsStore']);
Route::get('calculPointsStore',[\App\Http\Controllers\ProgressionController::class, 'calculPointsStore']);


/********************************************************************************** End Progression Controller route **************************************************************************************/

