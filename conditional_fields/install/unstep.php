// unstep.php - Wird nach der Deinstallation angezeigt
<?php
if(!check_bitrix_sessid()) return;

echo CAdminMessage::ShowNote("Modul 'Bedingte Felder für Smart-Prozesse' wurde deinstalliert");
?>
<form action="<?=$APPLICATION->GetCurPage()?>">
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <input type="submit" name="" value="Zurück zur Liste">
</form>