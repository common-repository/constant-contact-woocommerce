import HandleSettingsPage from './handleSettingsPage';
import HandleAdminNotifDismiss from "./handleAdminNotifDismiss";

// Handles store details.
const enableStoreDetails = new HandleSettingsPage();
const enableAdminNotifDismiss = new HandleAdminNotifDismiss();
window.onload = function(e)
{
    enableStoreDetails.init();
    enableAdminNotifDismiss.init();
};
