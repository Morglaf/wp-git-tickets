<?php
/**
 * Build .pot / .po / .mo for wp-git-tickets (run: php bin/build-translations.php).
 *
 * @package WPGIT
 */

$root = dirname( __DIR__ );
$lang = $root . '/languages';
if ( ! is_dir( $lang ) ) {
	mkdir( $lang, 0777, true );
}

$catalog = array(
	'Access denied.' => array(
		'fr_FR' => 'Accès refusé.',
		'es_ES' => 'Acceso denegado.',
		'de_DE' => 'Zugriff verweigert.',
	),
	'WP Git Tickets' => array(
		'fr_FR' => 'WP Git Tickets',
		'es_ES' => 'WP Git Tickets',
		'de_DE' => 'WP Git Tickets',
	),
	'GitHub configuration' => array(
		'fr_FR' => 'Configuration GitHub',
		'es_ES' => 'Configuración de GitHub',
		'de_DE' => 'GitHub-Konfiguration',
	),
	'Personal Access Token' => array(
		'fr_FR' => 'Jeton d’accès personnel',
		'es_ES' => 'Token de acceso personal',
		'de_DE' => 'Persönliches Zugriffstoken',
	),
	'Organization / username' => array(
		'fr_FR' => 'Organisation / pseudo',
		'es_ES' => 'Organización / usuario',
		'de_DE' => 'Organisation / Benutzername',
	),
	'Debug mode' => array(
		'fr_FR' => 'Mode débogage',
		'es_ES' => 'Modo de depuración',
		'de_DE' => 'Debug-Modus',
	),
	'Leave blank to keep the current token' => array(
		'fr_FR' => 'Laisser vide pour conserver le jeton actuel',
		'es_ES' => 'Dejar en blanco para mantener el token actual',
		'de_DE' => 'Leer lassen, um das aktuelle Token zu behalten',
	),
	'GitHub personal access token' => array(
		'fr_FR' => 'Jeton d’accès personnel GitHub',
		'es_ES' => 'Token de acceso personal de GitHub',
		'de_DE' => 'GitHub persönliches Zugriffstoken',
	),
	'A token is already saved. Leave the field empty to keep it.' => array(
		'fr_FR' => 'Un jeton est déjà enregistré. Laissez le champ vide pour le conserver.',
		'es_ES' => 'Ya hay un token guardado. Deje el campo vacío para conservarlo.',
		'de_DE' => 'Ein Token ist bereits gespeichert. Feld leer lassen, um es zu behalten.',
	),
	'e.g. MyOrganization' => array(
		'fr_FR' => 'ex. MonOrganisation',
		'es_ES' => 'p. ej. MiOrganizacion',
		'de_DE' => 'z. B. MeineOrganisation',
	),
	'Enable debug logging (PHP error_log)' => array(
		'fr_FR' => 'Activer les logs de débogage (PHP error_log)',
		'es_ES' => 'Activar registros de depuración (PHP error_log)',
		'de_DE' => 'Debug-Protokollierung aktivieren (PHP error_log)',
	),
	'WP Git Tickets settings' => array(
		'fr_FR' => 'Réglages WP Git Tickets',
		'es_ES' => 'Ajustes de WP Git Tickets',
		'de_DE' => 'WP Git Tickets Einstellungen',
	),
	'GitHub cache cleared.' => array(
		'fr_FR' => 'Cache GitHub vidé.',
		'es_ES' => 'Caché de GitHub vaciada.',
		'de_DE' => 'GitHub-Cache geleert.',
	),
	'Clear GitHub cache (README / feeds)' => array(
		'fr_FR' => 'Vider le cache GitHub (README / flux)',
		'es_ES' => 'Vaciar la caché de GitHub (README / feeds)',
		'de_DE' => 'GitHub-Cache leeren (README / Feeds)',
	),
	'Repository visibility' => array(
		'fr_FR' => 'Visibilité des dépôts',
		'es_ES' => 'Visibilidad de los repositorios',
		'de_DE' => 'Repository-Sichtbarkeit',
	),
	'Uncheck repositories you do not want in the contact form (e.g. .github, website).' => array(
		'fr_FR' => 'Décochez les dépôts que vous ne souhaitez pas dans le formulaire (ex. .github, site web).',
		'es_ES' => 'Desmarque los repositorios que no quiera en el formulario (p. ej. .github, sitio web).',
		'de_DE' => 'Deaktivieren Sie Repositories, die nicht im Formular erscheinen sollen (z. B. .github, Website).',
	),
	'Unable to list repositories:' => array(
		'fr_FR' => 'Impossible de lister les dépôts :',
		'es_ES' => 'No se pueden listar los repositorios:',
		'de_DE' => 'Repositories können nicht aufgelistet werden:',
	),
	'Repository name' => array(
		'fr_FR' => 'Nom du dépôt',
		'es_ES' => 'Nombre del repositorio',
		'de_DE' => 'Repository-Name',
	),
	'Note: unchecked repositories are ignored by the contact form.' => array(
		'fr_FR' => 'Note : les dépôts décochés sont ignorés par le formulaire de contact.',
		'es_ES' => 'Nota: los repositorios desmarcados se ignoran en el formulario de contacto.',
		'de_DE' => 'Hinweis: deaktivierte Repositories werden vom Kontaktformular ignoriert.',
	),
	'Technical error.' => array(
		'fr_FR' => 'Erreur technique.',
		'es_ES' => 'Error técnico.',
		'de_DE' => 'Technischer Fehler.',
	),
	'--- Choose a software ---' => array(
		'fr_FR' => '--- Choisir un logiciel ---',
		'es_ES' => '--- Elegir un software ---',
		'de_DE' => '--- Software wählen ---',
	),
	'Unknown website visitor' => array(
		'fr_FR' => 'Visiteur inconnu du site web',
		'es_ES' => 'Visitante desconocido del sitio web',
		'de_DE' => 'Unbekannter Website-Besucher',
	),
	'Submitted by: %s' => array(
		'fr_FR' => 'Posté par : %s',
		'es_ES' => 'Enviado por: %s',
		'de_DE' => 'Eingereicht von: %s',
	),
	'Repository URL is missing.' => array(
		'fr_FR' => 'URL du dépôt manquante.',
		'es_ES' => 'Falta la URL del repositorio.',
		'de_DE' => 'Repository-URL fehlt.',
	),
	'Invalid GitHub URL.' => array(
		'fr_FR' => 'URL GitHub invalide.',
		'es_ES' => 'URL de GitHub no válida.',
		'de_DE' => 'Ungültige GitHub-URL.',
	),
	'Invalid GitHub URL (owner/repo expected).' => array(
		'fr_FR' => 'URL GitHub invalide (owner/repo attendu).',
		'es_ES' => 'URL de GitHub no válida (se espera owner/repo).',
		'de_DE' => 'Ungültige GitHub-URL (owner/repo erwartet).',
	),
	'ACF/SCF field name is missing.' => array(
		'fr_FR' => 'Nom du champ ACF/SCF manquant.',
		'es_ES' => 'Falta el nombre del campo ACF/SCF.',
		'de_DE' => 'ACF/SCF-Feldname fehlt.',
	),
	'The ACF/SCF field is empty.' => array(
		'fr_FR' => 'Le champ ACF/SCF est vide.',
		'es_ES' => 'El campo ACF/SCF está vacío.',
		'de_DE' => 'Das ACF/SCF-Feld ist leer.',
	),
	'GitHub rate limit reached. Please try again later.' => array(
		'fr_FR' => 'Limite de taux GitHub atteinte. Réessayez plus tard.',
		'es_ES' => 'Se alcanzó el límite de velocidad de GitHub. Inténtelo más tarde.',
		'de_DE' => 'GitHub-Ratenlimit erreicht. Bitte später erneut versuchen.',
	),
	'GitHub resource not found.' => array(
		'fr_FR' => 'Ressource GitHub introuvable.',
		'es_ES' => 'Recurso de GitHub no encontrado.',
		'de_DE' => 'GitHub-Ressource nicht gefunden.',
	),
	'GitHub error (%d)' => array(
		'fr_FR' => 'Erreur GitHub (%d)',
		'es_ES' => 'Error de GitHub (%d)',
		'de_DE' => 'GitHub-Fehler (%d)',
	),
	'Incomplete configuration.' => array(
		'fr_FR' => 'Configuration incomplète.',
		'es_ES' => 'Configuración incompleta.',
		'de_DE' => 'Unvollständige Konfiguration.',
	),
	'No README.md found at the repository root.' => array(
		'fr_FR' => 'Aucun README.md trouvé à la racine du dépôt.',
		'es_ES' => 'No se encontró README.md en la raíz del repositorio.',
		'de_DE' => 'Kein README.md im Repository-Stamm gefunden.',
	),
	'Unable to read README file(s).' => array(
		'fr_FR' => 'Impossible de lire le(s) README.',
		'es_ES' => 'No se puede(n) leer el/los README.',
		'de_DE' => 'README-Datei(en) können nicht gelesen werden.',
	),
	'Push of %1$d commit(s) to %2$s%3$s' => array(
		'fr_FR' => 'Push de %1$d commit(s) sur %2$s%3$s',
		'es_ES' => 'Push de %1$d commit(s) a %2$s%3$s',
		'de_DE' => 'Push von %1$d Commit(s) nach %2$s%3$s',
	),
	'Release %1$s on %2$s' => array(
		'fr_FR' => 'Release %1$s sur %2$s',
		'es_ES' => 'Release %1$s en %2$s',
		'de_DE' => 'Release %1$s auf %2$s',
	),
	'Created %1$s %2$s' => array(
		'fr_FR' => 'Création %1$s %2$s',
		'es_ES' => 'Creación %1$s %2$s',
		'de_DE' => 'Erstellt %1$s %2$s',
	),
	'Starred %s' => array(
		'fr_FR' => 'Star sur %s',
		'es_ES' => 'Star en %s',
		'de_DE' => 'Stern für %s',
	),
	'Forked %s' => array(
		'fr_FR' => 'Fork de %s',
		'es_ES' => 'Fork de %s',
		'de_DE' => 'Fork von %s',
	),
	'— Select a field —' => array(
		'fr_FR' => '— Choisir un champ —',
		'es_ES' => '— Seleccionar un campo —',
		'de_DE' => '— Feld auswählen —',
	),
	'No activity.' => array(
		'fr_FR' => 'Aucune activité.',
		'es_ES' => 'Sin actividad.',
		'de_DE' => 'Keine Aktivität.',
	),
	'Error while rendering the feed.' => array(
		'fr_FR' => 'Erreur lors du rendu du flux.',
		'es_ES' => 'Error al representar el feed.',
		'de_DE' => 'Fehler beim Rendern des Feeds.',
	),
	'Organization is not configured.' => array(
		'fr_FR' => 'Organisation non configurée.',
		'es_ES' => 'La organización no está configurada.',
		'de_DE' => 'Organisation ist nicht konfiguriert.',
	),
	'Error while rendering the organization feed.' => array(
		'fr_FR' => 'Erreur lors du rendu du flux organisation.',
		'es_ES' => 'Error al representar el feed de la organización.',
		'de_DE' => 'Fehler beim Rendern des Organisations-Feeds.',
	),
	'Error while rendering the README.' => array(
		'fr_FR' => 'Erreur lors du rendu du README.',
		'es_ES' => 'Error al representar el README.',
		'de_DE' => 'Fehler beim Rendern der README.',
	),
	'Markdown rendering error.' => array(
		'fr_FR' => 'Erreur de rendu Markdown.',
		'es_ES' => 'Error de renderizado Markdown.',
		'de_DE' => 'Markdown-Renderfehler.',
	),
	// Block editor JS strings
	'Default (100%)' => array( 'fr_FR' => 'Par défaut (100%)', 'es_ES' => 'Predeterminado (100%)', 'de_DE' => 'Standard (100%)' ),
	'Custom…' => array( 'fr_FR' => 'Personnalisée…', 'es_ES' => 'Personalizado…', 'de_DE' => 'Benutzerdefiniert…' ),
	'Width' => array( 'fr_FR' => 'Largeur', 'es_ES' => 'Ancho', 'de_DE' => 'Breite' ),
	'Custom width' => array( 'fr_FR' => 'Largeur personnalisée', 'es_ES' => 'Ancho personalizado', 'de_DE' => 'Benutzerdefinierte Breite' ),
	'e.g. 640px, 80%, 40rem' => array( 'fr_FR' => 'Ex. 640px, 80%, 40rem', 'es_ES' => 'p. ej. 640px, 80%, 40rem', 'de_DE' => 'z. B. 640px, 80%, 40rem' ),
	'Repository source' => array( 'fr_FR' => 'Source du dépôt', 'es_ES' => 'Fuente del repositorio', 'de_DE' => 'Repository-Quelle' ),
	'Manual URL' => array( 'fr_FR' => 'URL manuelle', 'es_ES' => 'URL manual', 'de_DE' => 'Manuelle URL' ),
	'ACF / SCF field' => array( 'fr_FR' => 'Champ ACF / SCF', 'es_ES' => 'Campo ACF / SCF', 'de_DE' => 'ACF- / SCF-Feld' ),
	'Repository URL' => array( 'fr_FR' => 'URL du dépôt', 'es_ES' => 'URL del repositorio', 'de_DE' => 'Repository-URL' ),
	'Value read from the current post.' => array( 'fr_FR' => 'Valeur lue sur le post courant.', 'es_ES' => 'Valor leído de la entrada actual.', 'de_DE' => 'Wert aus dem aktuellen Beitrag.' ),
	'No ACF/SCF field detected. Enter the field name manually.' => array( 'fr_FR' => 'Aucun champ ACF/SCF détecté. Saisissez le nom du champ manuellement.', 'es_ES' => 'No se detectó ningún campo ACF/SCF. Introduzca el nombre manualmente.', 'de_DE' => 'Kein ACF-/SCF-Feld erkannt. Feldnamen manuell eingeben.' ),
	'ACF / SCF field name' => array( 'fr_FR' => 'Nom du champ ACF / SCF', 'es_ES' => 'Nombre del campo ACF / SCF', 'de_DE' => 'ACF- / SCF-Feldname' ),
	'Feed type' => array( 'fr_FR' => 'Type de flux', 'es_ES' => 'Tipo de feed', 'de_DE' => 'Feed-Typ' ),
	'Commits' => array( 'fr_FR' => 'Commits', 'es_ES' => 'Commits', 'de_DE' => 'Commits' ),
	'Activity (events)' => array( 'fr_FR' => 'Activité (events)', 'es_ES' => 'Actividad (events)', 'de_DE' => 'Aktivität (Events)' ),
	'Configure the block in the sidebar.' => array( 'fr_FR' => 'Configurez le bloc dans la barre latérale.', 'es_ES' => 'Configure el bloque en la barra lateral.', 'de_DE' => 'Konfigurieren Sie den Block in der Seitenleiste.' ),
	'Preview for ACF/SCF field "' => array( 'fr_FR' => 'Aperçu du champ ACF/SCF « ', 'es_ES' => 'Vista previa del campo ACF/SCF « ', 'de_DE' => 'Vorschau für ACF-/SCF-Feld „' ),
	'" — real content appears in preview / front-end.' => array( 'fr_FR' => ' » — le contenu réel s’affiche en prévisualisation / front.', 'es_ES' => ' » — el contenido real aparece en la vista previa / portada.', 'de_DE' => '“ — echter Inhalt erscheint in Vorschau / Frontend.' ),
	'ServerSideRender is unavailable.' => array( 'fr_FR' => 'ServerSideRender indisponible.', 'es_ES' => 'ServerSideRender no disponible.', 'de_DE' => 'ServerSideRender nicht verfügbar.' ),
	'Block render error.' => array( 'fr_FR' => 'Erreur de rendu du bloc.', 'es_ES' => 'Error de renderizado del bloque.', 'de_DE' => 'Block-Renderfehler.' ),
	'Feed settings' => array( 'fr_FR' => 'Paramètres du flux', 'es_ES' => 'Ajustes del feed', 'de_DE' => 'Feed-Einstellungen' ),
	'Number of items' => array( 'fr_FR' => 'Nombre d’éléments', 'es_ES' => 'Número de elementos', 'de_DE' => 'Anzahl der Einträge' ),
	'GitHub repository feed' => array( 'fr_FR' => 'Flux dépôt GitHub', 'es_ES' => 'Feed del repositorio GitHub', 'de_DE' => 'GitHub-Repository-Feed' ),
	'Organization' => array( 'fr_FR' => 'Organisation', 'es_ES' => 'Organización', 'de_DE' => 'Organisation' ),
	'Leave empty to use the organization from plugin settings.' => array( 'fr_FR' => 'Laisser vide pour utiliser l’organisation des réglages du plugin.', 'es_ES' => 'Dejar vacío para usar la organización de los ajustes del plugin.', 'de_DE' => 'Leer lassen, um die Organisation aus den Plugin-Einstellungen zu verwenden.' ),
	'README settings' => array( 'fr_FR' => 'Paramètres README', 'es_ES' => 'Ajustes del README', 'de_DE' => 'README-Einstellungen' ),
	'Branch' => array( 'fr_FR' => 'Branche', 'es_ES' => 'Rama', 'de_DE' => 'Branch' ),
	'Default: main (falls back to master).' => array( 'fr_FR' => 'Défaut : main (fallback master).', 'es_ES' => 'Predeterminado: main (alternativa master).', 'de_DE' => 'Standard: main (Fallback master).' ),
	'Default language' => array( 'fr_FR' => 'Langue par défaut', 'es_ES' => 'Idioma predeterminado', 'de_DE' => 'Standardsprache' ),
	'e.g. en for README.en.md. Empty = README.md.' => array( 'fr_FR' => 'Ex. en pour README.en.md. Vide = README.md.', 'es_ES' => 'p. ej. en para README.en.md. Vacío = README.md.', 'de_DE' => 'z. B. en für README.en.md. Leer = README.md.' ),
	'Show tabs when multiple READMEs exist' => array( 'fr_FR' => 'Afficher les onglets si plusieurs README', 'es_ES' => 'Mostrar pestañas si hay varios README', 'de_DE' => 'Tabs anzeigen, wenn mehrere READMEs vorhanden sind' ),
	'First tab label' => array( 'fr_FR' => 'Nom du premier onglet', 'es_ES' => 'Etiqueta de la primera pestaña', 'de_DE' => 'Bezeichnung des ersten Tabs' ),
	'Label for README.md (no language suffix). e.g. French, FR…' => array( 'fr_FR' => 'Libellé de README.md (sans suffixe langue). Ex. : Français, FR…', 'es_ES' => 'Etiqueta de README.md (sin sufijo de idioma). p. ej. Español, ES…', 'de_DE' => 'Bezeichnung für README.md (ohne Sprachsuffix). z. B. Deutsch, DE…' ),
	'Hide header (div align="center")' => array( 'fr_FR' => 'Masquer l’en-tête (div align="center")', 'es_ES' => 'Ocultar encabezado (div align="center")', 'de_DE' => 'Kopfzeile ausblenden (div align="center")' ),
	'Removes the top logos / badges / language links banner from the README.' => array( 'fr_FR' => 'Retire le bandeau logos / badges / liens de langues en haut du README.', 'es_ES' => 'Quita el banner de logos / insignias / enlaces de idioma del README.', 'de_DE' => 'Entfernt das Banner mit Logos / Badges / Sprachlinks oben in der README.' ),
	'Also hide (CSS selectors)' => array( 'fr_FR' => 'Masquer aussi (sélecteurs CSS)', 'es_ES' => 'Ocultar también (selectores CSS)', 'de_DE' => 'Auch ausblenden (CSS-Selektoren)' ),
	'One selector per line. e.g. div[align="center"]  ·  .header  ·  #badges' => array( 'fr_FR' => 'Un sélecteur par ligne. Ex. : div[align="center"]  ·  .header  ·  #badges', 'es_ES' => 'Un selector por línea. p. ej. div[align="center"]  ·  .header  ·  #badges', 'de_DE' => 'Ein Selektor pro Zeile. z. B. div[align="center"]  ·  .header  ·  #badges' ),
	'GitHub repository README' => array( 'fr_FR' => 'README dépôt GitHub', 'es_ES' => 'README del repositorio GitHub', 'de_DE' => 'GitHub-Repository-README' ),
);

function wpgit_po_escape( $str ) {
	return str_replace( array( '\\', '"', "\n", "\r", "\t" ), array( '\\\\', '\\"', '\\n', '', '\\t' ), $str );
}

function wpgit_write_po( $path, $headers, $entries, $locale = null ) {
	$out  = "msgid \"\"\nmsgstr \"\"\n";
	foreach ( $headers as $k => $v ) {
		$out .= '"' . $k . ': ' . $v . '\\n"' . "\n";
	}
	$out .= "\n";
	foreach ( $entries as $msgid => $translations ) {
		$msgstr = $locale ? ( $translations[ $locale ] ?? $msgid ) : '';
		$out   .= 'msgid "' . wpgit_po_escape( $msgid ) . "\"\n";
		$out   .= 'msgstr "' . wpgit_po_escape( $msgstr ) . "\"\n\n";
	}
	file_put_contents( $path, $out );
}

/**
 * Minimal .mo writer (no plurals).
 *
 * @param string $path Destination.
 * @param array  $map  msgid => msgstr.
 */
function wpgit_write_mo( $path, $map ) {
	$ids  = '';
	$strs = '';
	$offsets = array();
	foreach ( $map as $id => $str ) {
		$offsets[] = array( strlen( $ids ), strlen( $id ), strlen( $strs ), strlen( $str ) );
		$ids      .= $id . "\0";
		$strs     .= $str . "\0";
	}
	$count      = count( $map );
	$key_start  = 28 + ( $count * 16 );
	$value_start = $key_start + strlen( $ids );
	$out         = pack( 'V*', 0x950412de, 0, $count, 28, 28 + ( $count * 8 ), 0, $key_start );
	// Original table then translation table (each entry: length, offset).
	$table = '';
	$o     = $key_start;
	foreach ( $offsets as $off ) {
		$table .= pack( 'V*', $off[1], $o );
		$o     += $off[1] + 1;
	}
	$o = $value_start;
	foreach ( $offsets as $off ) {
		$table .= pack( 'V*', $off[3], $o );
		$o     += $off[3] + 1;
	}
	file_put_contents( $path, $out . $table . $ids . $strs );
}

$headers_pot = array(
	'Project-Id-Version' => 'WP Git Tickets 2.5.0',
	'Report-Msgid-Bugs-To' => 'https://github.com/Morglaf/wp-git-tickets',
	'POT-Creation-Date' => gmdate( 'Y-m-d H:iO' ),
	'MIME-Version' => '1.0',
	'Content-Type' => 'text/plain; charset=UTF-8',
	'Content-Transfer-Encoding' => '8bit',
	'X-Domain' => 'wp-git-tickets',
);

wpgit_write_po( $lang . '/wp-git-tickets.pot', $headers_pot, $catalog, null );

foreach ( array( 'fr_FR', 'es_ES', 'de_DE' ) as $locale ) {
	$headers = array_merge(
		$headers_pot,
		array(
			'Language' => $locale,
			'Plural-Forms' => 'nplurals=2; plural=(n != 1);',
		)
	);
	$po = $lang . '/wp-git-tickets-' . $locale . '.po';
	wpgit_write_po( $po, $headers, $catalog, $locale );

	$map = array();
	foreach ( $catalog as $msgid => $tr ) {
		$map[ $msgid ] = $tr[ $locale ] ?? $msgid;
	}
	wpgit_write_mo( $lang . '/wp-git-tickets-' . $locale . '.mo', $map );
	echo "Wrote $locale\n";
}

echo "Done.\n";
