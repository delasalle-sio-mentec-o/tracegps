<?php
    // Projet TraceGPS - services web
    // fichier : services/EnvoyerPosition.php
    // Dernière mise à jour : 27/11/2018 par Colleu
    
    // Les paramètres peuvent être passés par la méthode GET (pratique pour les tests, mais à éviter en exploitation) :
    //     http://<hébergeur>/SupprimerUnUtilisateur.php?pseudo=admin&mdpSha1=ff9fff929a1292db1c00e3142139b22ee4925177&pseudoAsupprimer=oxygen&lang=xml
    
    // Les paramètres peuvent être passés par la méthode POST (à privilégier en exploitation pour la confidentialité des données) :
    //     http://<hébergeur>/SupprimerUnUtilisateur.php
    
    // connexion du serveur web à la base MySQL
    include_once ('../modele/DAO.class.php');
    $dao = new DAO();
    
    // Récupération des données transmises
    
    if ( empty ($_REQUEST ["pseudo"]) == true)  $pseudo = "";  else   $pseudo = $_REQUEST ["pseudo"];
    if ( empty ($_REQUEST ["mdpSha1"]) == true)  $mdpSha1 = "";  else   $mdpSha1 = $_REQUEST ["mdpSha1"];
    if ( empty ($_REQUEST ["idTrace"]) == true)  $idTrace = "";  else   $idTrace = $_REQUEST ["idTrace"];
    if ( empty ($_REQUEST ["dateHeure"]) == true)  $dateHeure = "";  else   $dateHeure = $_REQUEST ["dateHeure"];
    if ( empty ($_REQUEST ["latitude"]) == true)  $latitude = "";  else   $latitude = $_REQUEST ["latitude"];
    if ( empty ($_REQUEST ["longitude"]) == true)  $longitude = "";  else   $longitude = $_REQUEST ["longitude"];
    if ( empty ($_REQUEST ["altitude"]) == true)  $altitude = "";  else   $altitude = $_REQUEST ["altitude"];
    if ( empty ($_REQUEST ["rythmeCardio"]) == true)  $rythmeCardio = "";  else   $rythmeCardio = $_REQUEST ["rythmeCardio"];
    if ( empty ($_REQUEST ["lang"]) == true) $lang = "";  else $lang = strtolower($_REQUEST ["lang"]);
    
    // "xml" par défaut si le paramètre lang est absent ou incorrect
    if ($lang != "json") $lang = "xml";
    // Contrôle de la présence des paramètres
    if ( $pseudo == "" || $mdpSha1 == "" || $idTrace == "" || $dateHeure == "" || $latitude == "" || $longitude == "" || $altitude == "" || $rythmeCardio == "" )
    {	
        $msg = "Erreur : données incomplètes.";
    } 
    else
    {	
        if ($dao->getNiveauConnexion($pseudo, $mdpSha1) == 0)
        {  
            $msg = "Erreur : authentification incorrecte.";
        }   
        else
        {	
            $unUtilisateur = $dao->getUnUtilisateur($pseudo);       
            $uneTrace = $dao->getUneTrace($idTrace);
            
            if ($uneTrace == null)
            {  
                $msg = "Erreur : le numéro de trace n'existe pas.";
            }
            else
            {   
                if ($uneTrace->getIdUtilisateur() != $unUtilisateur->getId())
                {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                }
                else 
                {
                    if($uneTrace->getTerminee()==1)
                    {
                        $msg = "Erreur : trace terminée.";
                    }
                    else
                    {
                        $idRealtif = sizeof($uneTrace->getLesPointsDeTrace())+1;
                        $unPoint = new PointDeTrace($idTrace,$idRealtif, $latitude, $longitude, $altitude, $dateHeure, $rythmeCardio, 0, 0, 0);
                        $ok = $dao->creerUnPointDeTrace($unPoint);
                        if ( $ok == false ) 
                        {
                            $msg = "Erreur : problème lors de l'enregistrement du point.";
                        }
                        else 
                        {
                            $msg = "Point créé.";
                        }
                    }
                }
            }
        }
    }
    // ferme la connexion à MySQL
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
    {	// crée une instance de DOMdocument (DOM : Document Object Model)
        $doc = new DOMDocument();
        
        // specifie la version et le type d'encodage
        $doc->version = '1.0';
        $doc->encoding = 'UTF-8';
        
        // crée un commentaire et l'encode en UTF-8
        $elt_commentaire = $doc->createComment('Service web EnvoyerPosition - BTS SIO - Lycée De La Salle - Rennes');
        // place ce commentaire à la racine du document XML
        $doc->appendChild($elt_commentaire);
        
        // crée l'élément 'data' à la racine du document XML
        $elt_data = $doc->createElement('data');
        $doc->appendChild($elt_data);
        
        // place l'élément 'reponse' dans l'élément 'data'
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


