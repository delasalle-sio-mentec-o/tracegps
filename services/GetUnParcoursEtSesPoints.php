<?php
//http://localhost/ws-php-xxx/tracegps/services/GetUnParcoursEtSesPoints.php?
//pseudo=europa
//&mdpSha1=13e3668bbee30b004380052b086457b014504b3e
//&idTrace=2
//&lang=json


include_once ('../modele/DAO.class.php');
$dao = new DAO();
	
// Récupération des données transmises
// la fonction $_GET récupère une donnée passée en paramètre dans l'URL par la méthode GET
// la fonction $_POST récupère une donnée envoyées par la méthode POST
// la fonction $_REQUEST récupère par défaut le contenu des variables $_GET, $_POST, $_COOKIE
if ( empty ($_REQUEST ["pseudo"]) == true)  $pseudo = "";  else   $pseudo = $_REQUEST ["pseudo"];
if ( empty ($_REQUEST ["mdpSha1"]) == true)  $mdpSha1 = "";  else   $mdpSha1 = $_REQUEST ["mdpSha1"];
if ( empty ($_REQUEST ["idTrace"]) == true)  $idTrace = "";  else   $idTrace = $_REQUEST ["idTrace"];
if ( empty ($_REQUEST ["lang"]) == true) $lang = "";  else $lang = strtolower($_REQUEST ["lang"]);
// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// Contrôle de la présence des paramètres
if ( $pseudo == "" || $mdpSha1 == "" || $idTrace == "" || $lang == "")
{	$msg = "Erreur : données incomplètes.";
}
else
{	// il faut être utilisateur pour supprimer un parcours
    if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 )
    {   $msg = "Erreur : authentification incorrecte.";
    }   //
	else 
	{	// contrôle d'existence de idTrace
	    $uneTrace = $dao->getUneTrace($idTrace);
	    $unUtilisateur = $dao->getUnUtilisateur($pseudo);
	    if ($uneTrace == null)
	    {  $msg = "Erreur : parcours inexistant.";
	    }
	    else
	    {   // si cet utilisateur possède encore des traces, sa suppression est refusée
	        if ( $uneTrace->getIdUtilisateur() != $unUtilisateur->getId() ) {
	            $msg = "Erreur : vous n'êtes pas le propriétaire de ce parcours.";
	        }
	        else {
	            // suppression du parcours dans la BDD
	           $uneTrace = $dao->getUneTrace($idTrace);
	           if ( $uneTrace ) {
	                $msg = "Données de la trace demandée.";
                }
                else {
                    // tout a fonctionné
                    $msg = "Erreur : parcours inexistant.";
                }
            }
	    }
	}
}
// ferme la connexion à MySQL
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    creerFluxXML($msg, $uneTrace);
}
else {
    creerFluxJSON($msg, $uneTrace);
}

// fin du programme (pour ne pas enchainer sur la fonction qui suit)
exit;
 


// création du flux XML en sortie
function creerFluxXML($msg, $uneTrace)
{	    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web GetTousLesUtilisateurs - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' dans l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    
    
    
    // traitement des utilisateurs
    if (sizeof($uneTrace) > 0) {
        // place l'élément 'donnees' dans l'élément 'data'
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);
        
        // place l'élément 'lesUtilisateurs' dans l'élément 'donnees'
        $elt_lesParcoursDunUtilisateur = $doc->createElement('t');
        //$elt_donnees->appendChild($elt_lesParcoursDunUtilisateur);
        
        // crée un élément vide 'utilisateur'
        $elt_ParcourDunUtilisateur = $doc->createElement('trace');
        // place l'élément 'utilisateur' dans l'élément 'lesUtilisateurs'
        $elt_donnees->appendChild($elt_ParcourDunUtilisateur);
        
        // crée les éléments enfants de l'élément 'utilisateur'
        $elt_id         = $doc->createElement('id', $uneTrace->getId());
        $elt_ParcourDunUtilisateur->appendChild($elt_id);
        
        $elt_dateHeureDebut    = $doc->createElement('dateHeureDebut', $uneTrace->getDateHeureDebut());
        $elt_ParcourDunUtilisateur->appendChild($elt_dateHeureDebut);
        
        $elt_terminee    = $doc->createElement('terminee', $uneTrace->getTerminee());
        $elt_ParcourDunUtilisateur->appendChild($elt_terminee);
        if ($uneTrace->getDateHeureFin() != null)
        {
            $elt_dateHeureFin     = $doc->createElement('dateHeureFin', $uneTrace->getDateHeureFin());
            $elt_ParcourDunUtilisateur->appendChild($elt_dateHeureFin);
        }

        
        $elt_idUtilisateur = $doc->createElement('idUtilisateur', $uneTrace->getIdUtilisateur());
        $elt_ParcourDunUtilisateur->appendChild($elt_idUtilisateur);
        
        $elt_PointUneTrace = $doc->createElement('lesPoints');
        $elt_donnees->appendChild($elt_PointUneTrace);
        
            
        foreach ($uneTrace->getLesPointsDeTrace() as $unParcourDunUtilisateur)
        {
            // crée un élément vide 'utilisateur'
            $elt_ParcourDunUtilisateur = $doc->createElement('point');
            $elt_PointUneTrace->appendChild($elt_ParcourDunUtilisateur);
            
            
            
            // crée les éléments enfants de l'élément 'utilisateur'
            $elt_id         = $doc->createElement('id', $unParcourDunUtilisateur->getId());
            $elt_ParcourDunUtilisateur->appendChild($elt_id);
            
            $elt_latitude   = $doc->createElement('latitude', $unParcourDunUtilisateur->getLatitude());
            $elt_ParcourDunUtilisateur->appendChild($elt_latitude);
            
            $elt_longitude    = $doc->createElement('longitude', $unParcourDunUtilisateur->getLongitude());
            $elt_ParcourDunUtilisateur->appendChild($elt_longitude);
            
            $elt_altitude     = $doc->createElement('altitude', $unParcourDunUtilisateur->getAltitude());
            $elt_ParcourDunUtilisateur->appendChild($elt_altitude);
            
            $elt_dateHeure     = $doc->createElement('dateHeure', $unParcourDunUtilisateur->getDateHeure());
            $elt_ParcourDunUtilisateur->appendChild($elt_dateHeure);
            
            $elt_rythmeCardio = $doc->createElement('rythmeCardio', $unParcourDunUtilisateur->getRythmeCardio());
            $elt_ParcourDunUtilisateur->appendChild($elt_rythmeCardio);
        }
    }
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    echo $doc->saveXML();
    return;
}
function creerFluxJSON($msg, $uneTrace)
{
    if (sizeof($uneTrace) == 0) {
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg];
    }
    else {
        // construction d'un tableau contenant les utilisateurs
        $lesObjetsDuTableau = array();
        
        $unObjetTrace = array();
        $lesObjetsDuTableauTrace = array();
        
        $unObjetParcourDunUtilisateur = array();
        $unObjetTrace["id"] = $uneTrace->getId();
        $unObjetTrace["dateHeureDebut"] = $uneTrace->getDateHeureDebut();
        $unObjetTrace["terminee"] = $uneTrace->getTerminee();
        if ($uneTrace->getDateHeureFin() != null)
            $unObjetTrace["dateHeureFin"] = $uneTrace->getDateHeureFin();
            
        $unObjetTrace["idUtilisateur"] = $uneTrace->getIdUtilisateur();
        $lesObjetsDuTableauTrace[] = $unObjetTrace;
        
        foreach ($uneTrace->getLesPointsDeTrace() as $unParcourDunUtilisateur)
        {	// crée une ligne dans le tableau
            
            $unObjetParcourDunUtilisateur = array();
            $unObjetParcourDunUtilisateur["id"] = $unParcourDunUtilisateur->getId();
            $unObjetParcourDunUtilisateur["latitude"] = $unParcourDunUtilisateur->getLatitude();
            $unObjetParcourDunUtilisateur["longitude"] = $unParcourDunUtilisateur->getLongitude();
            $unObjetParcourDunUtilisateur["altitude"] = $unParcourDunUtilisateur->getAltitude();
                
            $unObjetParcourDunUtilisateur["dateHeure"] = $unParcourDunUtilisateur->getDateHeure();
            $unObjetParcourDunUtilisateur["rythmeCardio"] = $unParcourDunUtilisateur->getRythmeCardio();
            $lesObjetsDuTableau[] = $unObjetParcourDunUtilisateur;
        }
        // construction de l'élément "lesTraces"
        
        
        $elt_ParcoursDunUtilisateur = ["trace" => $lesObjetsDuTableauTrace, "lesPoints" => $lesObjetsDuTableau];
        
        
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg, "donnees" => $elt_ParcoursDunUtilisateur];
    }
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    echo json_encode($elt_racine, JSON_PRETTY_PRINT);
    return;
}
?>