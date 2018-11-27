<?php
// Projet TraceGPS - services web
// fichier : services/GetTousLesUtilisateurs.php
// Dernière mise à jour : 14/11/2018 par Jim

// Rôle : ce service permet à un utilisateur authentifié d'obtenir la liste de tous les utilisateurs (de niveau 1)
// Le service web doit recevoir 3 paramètres :
//     pseudo : le pseudo de l'utilisateur
//     mdpSha1 : le mot de passe de l'utilisateur hashé en sha1
//     lang : le langage du flux de données retourné ("xml" ou "json") ; "xml" par défaut si le paramètre est absent ou incorrect
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// Les paramètres peuvent être passés par la méthode GET (pratique pour les tests, mais à éviter en exploitation) :
//     http://<hébergeur>/GetTousLesUtilisateurs.php?pseudo=callisto&mdpSha1=13e3668bbee30b004380052b086457b014504b3e&lang=xml

// Les paramètres peuvent être passés par la méthode POST (à privilégier en exploitation pour la confidentialité des données) :
//     http://<hébergeur>/GetTousLesUtilisateurs.php

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
if ( empty ($_REQUEST ["pseudoARetirer"]) == true) $pseudoARetirer = "";  else $pseudoARetirer = strtolower($_REQUEST ["pseudoARetirer"]);
if ( empty ($_REQUEST ["texteMessage"]) == true) $texteMessage = "";  else $texteMessage = strtolower($_REQUEST ["texteMessage"]);
// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";



// Contrôle de la présence des paramètres
if ( $pseudo == "" || $mdpSha1 == "" || $pseudoARetirer == "")
{	$msg = "Erreur : données incomplètes.";
}
else
{	if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 )
		$msg = "Erreur : authentification incorrecte.";
	else 
	{	// récupération de la liste des utilisateurs à l'aide de la méthode getTousLesUtilisateurs de la classe DAO
	    $destinataire = $dao->getUnUtilisateur($pseudoARetirer);
	    $utilisateur = $dao->getUnUtilisateur($pseudo);
	    
	    if(! $destinataire)
	    {
	        $msg = "Erreur : pseudo utilisateur inexistant.";
	    }
	    else {
    	    $destinataireid = $destinataire->getId();
    	    $utilisateurid = $utilisateur->getId();
    	    
    	    $oui = $dao->autoriseAConsulter($utilisateurid, $destinataireid);
    	    
    	    if (! $oui)
    	    {
    	        $msg = "Erreur : l'autorisation n'était pas accordée.";
    	    }
    	    else {
    	        $supprimer = $dao->supprimerUneAutorisation ($utilisateurid, $destinataireid);
    	        if ($texteMessage == "")
    	        {
    	            if ($supprimer){
    	                $msg = "Autorisation supprimée.";
    	            }
    	            else{
    	                $msg = "Erreur : autorisation supprimée.";
    	            }
    	        }
    	        else 
    	        {
    	            
        	        $ok = Outils::envoyerMail($destinataire->getAdrMail(), "TraceGPS annulation de l'autorisation de ".$pseudo, "Cher ou chère ".$pseudoARetirer."\nL'utilisateur ".$pseudo." du système TraceGPS vous retire l'autorisation de suivre ses parcours.\nSon message :\n".$texteMessage."\n\nCordialement\n L'administrateur du système TraceGPS", $ADR_MAIL_EMETTEUR);
            	    if ($ok) {
            	        if ($supprimer){
            	            $msg = "Autorisation supprimée ; ";
            	        }
            	        else{
            	            $msg = "Erreur : autorisation supprimée ; ";
            	        }
            	        $msg .= $pseudoARetirer." va recevoir un courriel de notification.";
            	        
            	    }
            	    else{
            			
            			if ($supprimer){
            			    $msg = "Autorisation supprimée ; ";
            			}
            			else{
            			    $msg = "Erreur : autorisation supprimée ; ";
            			}
            			$msg .= $pseudoARetirer." l'envoi du courriel de notification a rencontré un problème.";
            	   }
    	        }
	       }
	    }
	}
}
// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    creerFluxXML($msg);
}
else {
    creerFluxJSON($msg);
}

// fin du programme (pour ne pas enchainer sur la fonction qui suit)
exit;


// création du flux XML en sortie
function creerFluxXML($msg)
{
    /* Exemple de code XML
     <?xml version="1.0" encoding="UTF-8"?>
     <!--Service web DemanderUneAutorisation - BTS SIO - Lycée De La Salle - Rennes-->
     <data>
     <reponse>Erreur : authentification incorrecte.</reponse>
     </data>
     */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web DemanderMdp - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    echo $doc->saveXML();
    return;
}

// création du flux JSON en sortie
function creerFluxJSON($msg)
{
    /* Exemple de code JSON
     {
     "data": {
     "reponse": "Erreur : authentification incorrecte."
     }
     }
     */
    
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    echo json_encode($elt_racine, JSON_PRETTY_PRINT);
    return;
    
    
}
?>
