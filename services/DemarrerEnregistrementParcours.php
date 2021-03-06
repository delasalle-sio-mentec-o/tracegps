<?php
// Projet TraceGPS - services web
// fichier : services/DemarrerEnregistrementParcours.php
// Dernière mise à jour : 14/11/2018 par Jim

// Rôle : ce service permet à un utilisateur authentifié de démarrer l'enregistrement d'un parcours
// Le service web doit recevoir 3 paramètres :
//     pseudo : le pseudo de l'utilisateur
//     mdpSha1 : le mot de passe de l'utilisateur hashé en sha1
//     lang : le langage du flux de données retourné ("xml" ou "json") ; "xml" par défaut si le paramètre est absent ou incorrect
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// Les paramètres peuvent être passés par la méthode GET (pratique pour les tests, mais à éviter en exploitation) :
//     http://<hébergeur>/DemarrerEnregistrementParcours.php?pseudo=callisto&mdpSha1=13e3668bbee30b004380052b086457b014504b3e&lang=xml

// Les paramètres peuvent être passés par la méthode POST (à privilégier en exploitation pour la confidentialité des données) :
//     http://<hébergeur>/DemarrerEnregistrementParcours.php

// connexion du serveur web à la base MySQL
include_once ('../modele/DAO.class.php');
$dao = new DAO();

// Récupération des données transmises
// la fonction $_GET récupère une donnée passée en paramètre dans l'URL par la méthode GET
// la fonction $_POST récupère une donnée envoyées par la méthode POST
// la fonction $_REQUEST récupère par défaut le contenu des variables $_GET, $_POST, $_COOKIE
if ( empty ($_REQUEST ["pseudo"]) == true)  $pseudo = "";  else   $pseudo = $_REQUEST ["pseudo"];
if ( empty ($_REQUEST ["mdpSha1"]) == true)  $mdpSha1 = "";  else   $mdpSha1 = $_REQUEST ["mdpSha1"];
if ( empty ($_REQUEST ["lang"]) == true) $lang = "";  else $lang = strtolower($_REQUEST ["lang"]);
// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// initialisation du nombre de réponses
$nbReponses = 0;
$uneTrace = array();

// Contrôle de la présence des paramètres
if ( $pseudo == "" || $mdpSha1 == "" )
{	$msg = "Erreur : données incomplètes.";
}
else
{	if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 )
    $msg = "Erreur : authentification incorrecte.";
    else
    {	// récupération des informations de l'utilisateur
        $utilisateur = $dao->getUnUtilisateur($pseudo);
        $lesTraces = $dao->getLesTraces($utilisateur->getId());
        $i = 0;
        foreach ($lesTraces as $uneTrace)
        {
            if($i < $uneTrace->getId())
            {
               $i = $uneTrace->getId();
            }
        }
        $uneTrace = new Trace($i+1, date("Y-m-d H-i-s"), null, 0, $utilisateur->getId());
        $ok = $dao->creerUneTrace($uneTrace);
        if ( ! $ok ) {
            $msg = "Erreur : problème lors de la création de la trace.";
        }
        else {
            // tout a fonctionné
            $msg = "Trace créée.";
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
{
    /* Exemple de code XML
     <?xml version="1.0" encoding="UTF-8"?>
     <!--Service web DemarrerEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes-->
     <data>
     <reponse>2 utilisateur(s).</reponse>
     <donnees>
     <lesUtilisateurs>
     <utilisateur>
     <id>2</id>
     <pseudo>callisto</pseudo>
     <adrMail>delasalle.sio.eleves@gmail.com</adrMail>
     <numTel>22.33.44.55.66</numTel>
     <niveau>1</niveau>
     <dateCreation>2018-08-12 19:45:23</dateCreation>
     <nbTraces>2</nbTraces>
     <dateDerniereTrace>2018-01-19 13:08:48</dateDerniereTrace>
     </utilisateur>
     <utilisateur>
     <id>3</id>
     <pseudo>europa</pseudo>
     <adrMail>delasalle.sio.eleves@gmail.com</adrMail>
     <numTel>22.33.44.55.66</numTel>
     <niveau>1</niveau>
     <dateCreation>2018-08-12 19:45:23</dateCreation>
     <nbTraces>0</nbTraces>
     </utilisateur>
     </lesUtilisateurs>
     </donnees>
     </data>
     */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web DemarrerEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes');
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
        //$elt_uneTrace = $doc->createElement('laTrace');
        //$elt_donnees->appendChild($elt_uneTrace);

            // crée un élément vide 'trace'
            $elt_trace = $doc->createElement('trace');
            // place l'élément 'trace' dans l'élément 'uneTrace'
            //$elt_uneTrace->appendChild($elt_trace);
            $elt_donnees->appendChild($elt_trace);
            
            // crée les éléments enfants de l'élément 'trace'
            $elt_id         = $doc->createElement('id', $uneTrace->getId());
            $elt_trace->appendChild($elt_id);
            
            $elt_dateHeureDebut     = $doc->createElement('dateHeureDebut', $uneTrace->getDateHeureDebut());
            $elt_trace->appendChild($elt_dateHeureDebut);
            
            $elt_terminee    = $doc->createElement('terminee',$uneTrace->getTerminee());
            $elt_trace->appendChild($elt_terminee);
            
            $elt_idUtilisateur    = $doc->createElement('idUtilisateur', $uneTrace->getIdUtilisateur());
            $elt_trace->appendChild($elt_idUtilisateur);
        
    }
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    echo $doc->saveXML();
    return;
}

// création du flux JSON en sortie
function creerFluxJSON($msg, $uneTrace)
{
    /* Exemple de code JSON
     {
     "data": {
     "reponse": "2 trace(s).",
     "donnees": {
     "lesUtilisateurs": [
     {
     "id": "2",
     "pseudo": "callisto",
     "adrMail": "delasalle.sio.eleves@gmail.com",
     "numTel": "22.33.44.55.66",
     "niveau": "1",
     "dateCreation": "2018-08-12 19:45:23",
     "nbTraces": "2",
     "dateDerniereTrace": "2018-01-19 13:08:48"
     },
     {
     "id": "3",
     "pseudo": "europa",
     "adrMail": "delasalle.sio.eleves@gmail.com",
     "numTel": "22.33.44.55.66",
     "niveau": "1",
     "dateCreation": "2018-08-12 19:45:23",
     "nbTraces": "0"
     }
     ]
     }
     }
     }
     */
    
    
    if (sizeof($uneTrace) == 0) {
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg];
    }
    else {
        // construction d'un tableau contenant les utilisateurs
        $lesObjetsDuTableau = array();

        $unObjetTrace = array();
        $unObjetTrace["id"] = $uneTrace->getId();
        $unObjetTrace["dateHeureDebut"] = $uneTrace->getDateHeureDebut();
        $unObjetTrace["terminee"] = $uneTrace->getTerminee();
        $unObjetTrace["idUtilisateur"] = $uneTrace->getIdUtilisateur();

        $lesObjetsDuTableau[] = $unObjetTrace;
        
        // construction de l'élément "lesUtilisateurs"
        $elt_trace = ["trace" => $lesObjetsDuTableau];
        
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg, "donnees" => $elt_trace];
    }
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    echo json_encode($elt_racine, JSON_PRETTY_PRINT);
    return;
}
?>
