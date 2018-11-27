<?php
include_once ('../modele/DAO.class.php');
$dao = new DAO();

if ( empty ($_REQUEST ["pseudo"]) == true)  $pseudo = "";  else   $pseudo = $_REQUEST ["pseudo"];
if ( empty ($_REQUEST ["mdpSha1"]) == true)  $mdpSha1 = "";  else   $mdpSha1 = $_REQUEST ["mdpSha1"];
if ( empty ($_REQUEST ["pseudoConsulte"]) == true) $lang = "";  else $pseudoConsulte = strtolower($_REQUEST ["pseudoConsulte"]);
if ( empty ($_REQUEST ["lang"]) == true) $lang = "";  else $lang = strtolower($_REQUEST ["lang"]);
// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// initialisation du nombre de réponses
$nbReponses = 0;
$lesParcoursDunUtilisateur = array();

// Contrôle de la présence des paramètres
if ( $pseudo == "" || $mdpSha1 == "" || $pseudoConsulte== "" )
{	$msg = "Erreur : données incomplètes.";
}
else
{	if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 )
    $msg = "Erreur : authentification incorrecte.";
    else
    {
        $utilisateur = $dao->getUnUtilisateur($pseudoConsulte);
        
        $lesParcoursDunUtilisateur = $dao->getLesTraces($utilisateur->getId());

        $nbReponses = sizeof($lesParcoursDunUtilisateur);
        
        if ($nbReponses == 0) {
            $msg = "Erreur : psoeudo consulté inexistant inexistant.";
        }
        else {
            $msg = $nbReponses . " autorisation(s) accordée(s) par " . $pseudoConsulte . ".";
        }
    }
}
// ferme la connexion à MySQL
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    creerFluxXML($msg, $lesParcoursDunUtilisateur);
}
else {
    creerFluxJSON($msg, $lesParcoursDunUtilisateur);
}

// fin du programme (pour ne pas enchainer sur la fonction qui suit)
exit;

// création du flux XML en sortie
function creerFluxXML($msg, $lesParcoursDunUtilisateur)
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
    if (sizeof($lesParcoursDunUtilisateur) > 0) {
        // place l'élément 'donnees' dans l'élément 'data'
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees);
        
        // place l'élément 'lesUtilisateurs' dans l'élément 'donnees'
        $elt_lesParcoursDunUtilisateur = $doc->createElement('lesUtilisateurs');
        $elt_donnees->appendChild($elt_lesParcoursDunUtilisateur);
        
        foreach ($lesParcoursDunUtilisateur as $unParcourDunUtilisateur)
        {
            // crée un élément vide 'utilisateur'
            $elt_ParcourDunUtilisateur = $doc->createElement('utilisateur');
            // place l'élément 'utilisateur' dans l'élément 'lesUtilisateurs'
            $elt_lesParcoursDunUtilisateur->appendChild($elt_ParcourDunUtilisateur);
            
            // crée les éléments enfants de l'élément 'utilisateur'
            $elt_id         = $doc->createElement('id', $unParcourDunUtilisateur->getId());
            $elt_ParcourDunUtilisateur->appendChild($elt_id);
            
            $elt_adrMail    = $doc->createElement('dateHeureDebut', $unParcourDunUtilisateur->getDateHeureDebut());
            $elt_ParcourDunUtilisateur->appendChild($elt_adrMail);
            
            $elt_adrMail    = $doc->createElement('terminee', $unParcourDunUtilisateur->getTerminee());
            $elt_ParcourDunUtilisateur->appendChild($elt_adrMail);
            
            $elt_numTel     = $doc->createElement('dateHeureFin', $unParcourDunUtilisateur->getDateHeureFin());
            $elt_ParcourDunUtilisateur->appendChild($elt_numTel);
            
            $elt_niveau     = $doc->createElement('distance', $unParcourDunUtilisateur->getDistanceTotale());
            $elt_ParcourDunUtilisateur->appendChild($elt_niveau);
            
            $elt_dateCreation = $doc->createElement('idUtilisateur', $unParcourDunUtilisateur->getIdUtilisateur());
            $elt_ParcourDunUtilisateur->appendChild($elt_dateCreation); 
        }
    }
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    echo $doc->saveXML();
    return;
}
function creerFluxJSON($msg, $lesParcoursDunUtilisateur)
{  
    if (sizeof($lesParcoursDunUtilisateur) == 0) {
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg];
    }
    else {
        // construction d'un tableau contenant les utilisateurs
        $lesObjetsDuTableau = array();
        foreach ($lesParcoursDunUtilisateur as $unParcourDunUtilisateur)
        {	// crée une ligne dans le tableau
            $unObjetParcourDunUtilisateur = array();
            $unObjetParcourDunUtilisateur["id"] = $unParcourDunUtilisateur->getId();
            $unObjetParcourDunUtilisateur["dateHeureDebut"] = $unParcourDunUtilisateur->getDateHeureDebut();
            $unObjetParcourDunUtilisateur["terminee"] = $unParcourDunUtilisateur->getTerminee();
            $unObjetParcourDunUtilisateur["dateHeureFin"] = $unParcourDunUtilisateur->getDateHeureFin();
            $unObjetParcourDunUtilisateur["distance"] = $unParcourDunUtilisateur->getDistance();
            $unObjetParcourDunUtilisateur["idUtilisateur"] = $unParcourDunUtilisateur->getIdUtilisateur();
            $unObjetParcourDunUtilisateur[] = $unObjetParcourDunUtilisateur;
        }
        // construction de l'élément "lesUtilisateurs"
        $elt_ParcoursDunUtilisateur = ["lesParcoursDunUtilisateur" => $lesObjetsDuTableau];
        
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