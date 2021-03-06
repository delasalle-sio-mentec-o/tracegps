﻿<?php
// Projet TraceGPS
// fichier : modele/DAO.class.php   (DAO : Data Access Object)
// Rôle : fournit des méthodes d'accès à la bdd tracegps (projet TraceGPS) au moyen de l'objet PDO
// modifié par Jim le 12/8/2018

// liste des méthodes déjà développées (dans l'ordre d'apparition dans le fichier) :

// __construct() : le constructeur crée la connexion $cnx à la base de données
// __destruct() : le destructeur ferme la connexion $cnx à la base de données
// getNiveauConnexion($login, $mdp) : fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $login et $mdp
// function existePseudoUtilisateur($pseudo) : fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
// getUnUtilisateur($login) : fournit un objet Utilisateur à partir de $login (son pseudo ou son adresse mail)
// getTousLesUtilisateurs() : fournit la collection de tous les utilisateurs (de niveau 1)
// creerUnUtilisateur($unUtilisateur) : enregistre l'utilisateur $unUtilisateur dans la bdd
// modifierMdpUtilisateur($login, $nouveauMdp) : enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $login daprès l'avoir hashé en SHA1
// supprimerUnUtilisateur($login) : supprime l'utilisateur $login (son pseudo ou son adresse mail) dans la bdd, ainsi que ses traces et ses autorisations
// envoyerMdp($login, $nouveauMdp) : envoie un mail à l'utilisateur $login avec son nouveau mot de passe $nouveauMdp

// liste des méthodes restant à développer :

// existeAdrMailUtilisateur($adrmail) : fournit true si l'adresse mail $adrMail existe dans la table tracegps_utilisateurs, false sinon
// getLesUtilisateursAutorises($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisés à suivre l'utilisateur $idUtilisateur
// getLesUtilisateursAutorisant($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisant l'utilisateur $idUtilisateur à voir leurs parcours
// autoriseAConsulter($idAutorisant, $idAutorise) : vérifie que l'utilisateur $idAutorisant) autorise l'utilisateur $idAutorise à consulter ses traces
// creerUneAutorisation($idAutorisant, $idAutorise) : enregistre l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// supprimerUneAutorisation($idAutorisant, $idAutorise) : supprime l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// getLesPointsDeTrace($idTrace) : fournit la collection des points de la trace $idTrace
// getUneTrace($idTrace) : fournit un objet Trace à partir de identifiant $idTrace
// getToutesLesTraces() : fournit la collection de toutes les traces
// getMesTraces($idUtilisateur) : fournit la collection des traces de l'utilisateur $idUtilisateur
// getLesTracesAutorisees($idUtilisateur) : fournit la collection des traces que l'utilisateur $idUtilisateur a le droit de consulter
// creerUneTrace(Trace $uneTrace) : enregistre la trace $uneTrace dans la bdd
// terminerUneTrace($idTrace) : enregistre la fin de la trace d'identifiant $idTrace dans la bdd ainsi que la date de fin
// supprimerUneTrace($idTrace) : supprime la trace d'identifiant $idTrace dans la bdd, ainsi que tous ses points
// creerUnPointDeTrace(PointDeTrace $unPointDeTrace) : enregistre le point $unPointDeTrace dans la bdd


// certaines méthodes nécessitent les classes suivantes :
include_once ('Utilisateur.class.php');
include_once ('Trace.class.php');
include_once ('PointDeTrace.class.php');
include_once ('Point.class.php');
include_once ('Outils.class.php');

// inclusion des paramètres de l'application
include_once ('parametres.php');

// début de la classe DAO (Data Access Object)
class DAO
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Membres privés de la classe ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $cnx;				// la connexion à la base de données
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Constructeur et destructeur ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function __construct() {
        global $PARAM_HOTE, $PARAM_PORT, $PARAM_BDD, $PARAM_USER, $PARAM_PWD;
        try
        {	$this->cnx = new PDO ("mysql:host=" . $PARAM_HOTE . ";port=" . $PARAM_PORT . ";dbname=" . $PARAM_BDD,
            $PARAM_USER,
            $PARAM_PWD);
        return true;
        }
        catch (Exception $ex)
        {	echo ("Echec de la connexion a la base de donnees <br>");
        echo ("Erreur numero : " . $ex->getCode() . "<br />" . "Description : " . $ex->getMessage() . "<br>");
        echo ("PARAM_HOTE = " . $PARAM_HOTE);
        return false;
        }
    }
    
    public function __destruct() {
        // ferme la connexion à MySQL :
        unset($this->cnx);
    }
    
    // ------------------------------------------------------------------------------------------------------
    // -------------------------------------- Méthodes d'instances ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    // fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $pseudo et $mdpSha1
    // cette fonction renvoie un entier :
    //     0 : authentification incorrecte
    //     1 : authentification correcte d'un utilisateur (pratiquant ou personne autorisée)
    //     2 : authentification correcte d'un administrateur
    // modifié par Jim le 11/1/2018
    public function getNiveauConnexion($pseudo, $mdpSha1) {
        // préparation de la requête de recherche
        $txt_req = "Select niveau from tracegps_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $txt_req .= " and mdpSha1 = :mdpSha1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        $req->bindValue("mdpSha1", $mdpSha1, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // traitement de la réponse
        $reponse = 0;
        if ($uneLigne) {
        	$reponse = $uneLigne->niveau;
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la réponse
        return $reponse;
    }
    
    
    // fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
    // modifié par Jim le 27/12/2017
    public function existePseudoUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select count(*) from tracegps_utilisateurs where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        $nbReponses = $req->fetchColumn(0);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // fourniture de la réponse
        if ($nbReponses == 0) {
            return false;
        }
        else {
            return true;
        }
    }
    
    
    // fournit un objet Utilisateur à partir de son pseudo $pseudo
    // fournit la valeur null si le pseudo n'existe pas
    // modifié par Jim le 9/1/2018
    public function getUnUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // traitement de la réponse
        if ( ! $uneLigne) {
            return null;
        }
        else {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            return $unUtilisateur;
        }
    }
    
    
    // fournit la collection  de tous les utilisateurs (de niveau 1)
    // le résultat est fourni sous forme d'une collection d'objets Utilisateur
    // modifié par Jim le 27/12/2017
    public function getTousLesUtilisateurs() {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where niveau = 1";
        $txt_req .= " order by pseudo";
        
        $req = $this->cnx->prepare($txt_req);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la collection
        return $lesUtilisateurs;
    }

    
    // enregistre l'utilisateur $unUtilisateur dans la bdd
    // fournit true si l'enregistrement s'est bien effectué, false sinon
    // met à jour l'objet $unUtilisateur avec l'id (auto_increment) attribué par le SGBD
    // modifié par Jim le 9/1/2018
    public function creerUnUtilisateur($unUtilisateur) {
        // on teste si l'utilisateur existe déjà
        if ($this->existePseudoUtilisateur($unUtilisateur->getPseudo())) return false;
        
        // préparation de la requête
        $txt_req1 = "insert into tracegps_utilisateurs (pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation)";
        $txt_req1 .= " values (:pseudo, :mdpSha1, :adrMail, :numTel, :niveau, :dateCreation)";
        $req1 = $this->cnx->prepare($txt_req1);
        // liaison de la requête et de ses paramètres
        $req1->bindValue("pseudo", utf8_decode($unUtilisateur->getPseudo()), PDO::PARAM_STR);
        $req1->bindValue("mdpSha1", utf8_decode(sha1($unUtilisateur->getMdpsha1())), PDO::PARAM_STR);
        $req1->bindValue("adrMail", utf8_decode($unUtilisateur->getAdrmail()), PDO::PARAM_STR);
        $req1->bindValue("numTel", utf8_decode($unUtilisateur->getNumTel()), PDO::PARAM_STR);
        $req1->bindValue("niveau", utf8_decode($unUtilisateur->getNiveau()), PDO::PARAM_INT);
        $req1->bindValue("dateCreation", utf8_decode($unUtilisateur->getDateCreation()), PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req1->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        // recherche de l'identifiant (auto_increment) qui a été attribué à la trace
        $txt_req2 = "Select max(id) as idMax from tracegps_utilisateurs";
        $req2 = $this->cnx->prepare($txt_req2);
        // extraction des données
        $req2->execute();
        $uneLigne = $req2->fetch(PDO::FETCH_OBJ);
        $unId = $uneLigne->idMax;
        $unUtilisateur->setId($unId);
        return true;
    }
    
    
    // enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $pseudo daprès l'avoir hashé en SHA1
    // fournit true si la modification s'est bien effectuée, false sinon
    // modifié par Jim le 9/1/2018
    public function modifierMdpUtilisateur($pseudo, $nouveauMdp) {
        // préparation de la requête
        $txt_req = "update tracegps_utilisateurs set mdpSha1 = :nouveauMdp";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("nouveauMdp", sha1($nouveauMdp), PDO::PARAM_STR);
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req->execute();
        return $ok;
    }
    
    
    // supprime l'utilisateur $pseudo dans la bdd, ainsi que ses traces et ses autorisations
    // fournit true si l'effacement s'est bien effectué, false sinon
    // modifié par Jim le 9/1/2018
    public function supprimerUnUtilisateur($pseudo) {
        $unUtilisateur = $this->getUnUtilisateur($pseudo);
        if ($unUtilisateur == null) {
            return false;
        }
        else {
            $idUtilisateur = $unUtilisateur->getId();
            
            // suppression des traces de l'utilisateur (et des points correspondants)
            $lesTraces = $this->getLesTraces($idUtilisateur);
            foreach ($lesTraces as $uneTrace) {
                $this->supprimerUneTrace($uneTrace->getId());
            }
            
            // préparation de la requête de suppression des autorisations
            $txt_req1 = "delete from tracegps_autorisations" ;
            $txt_req1 .= " where idAutorisant = :idUtilisateur or idAutorise = :idUtilisateur";
            $req1 = $this->cnx->prepare($txt_req1);
            // liaison de la requête et de ses paramètres
            $req1->bindValue("idUtilisateur", utf8_decode($idUtilisateur), PDO::PARAM_INT);
            // exécution de la requête
            $ok = $req1->execute();
            
            // préparation de la requête de suppression de l'utilisateur
            $txt_req2 = "delete from tracegps_utilisateurs" ;
            $txt_req2 .= " where pseudo = :pseudo";
            $req2 = $this->cnx->prepare($txt_req2);
            // liaison de la requête et de ses paramètres
            $req2->bindValue("pseudo", utf8_decode($pseudo), PDO::PARAM_STR);
            // exécution de la requête
            $ok = $req2->execute();
            return $ok;
        }
    }
    
    
    // envoie un mail à l'utilisateur $pseudo avec son nouveau mot de passe $nouveauMdp
    // retourne true si envoi correct, false en cas de problème d'envoi
    // modifié par Jim le 9/1/2018
    public function envoyerMdp($pseudo, $nouveauMdp) {
        global $ADR_MAIL_EMETTEUR;
        // si le pseudo n'est pas dans la table tracegps_utilisateurs :
        if ( $this->existePseudoUtilisateur($pseudo) == false ) return false;
        
        // recherche de l'adresse mail
        $adrMail = $this->getUnUtilisateur($pseudo)->getAdrMail();
        
        // envoie un mail à l'utilisateur avec son nouveau mot de passe
        $sujet = "Modification de votre mot de passe d'accès au service TraceGPS";
        $message = "Cher(chère) " . $pseudo . "\n\n";
        $message .= "Votre mot de passe d'accès au service service TraceGPS a été modifié.\n\n";
        $message .= "Votre nouveau mot de passe est : " . $nouveauMdp ;
        $ok = Outils::envoyerMail ($adrMail, $sujet, $message, $ADR_MAIL_EMETTEUR);
        return $ok;
    }
    
    
    // Le code restant à développer va être réparti entre les membres de l'équipe de développement.
    // Afin de limiter les conflits avec GitHub, il est décidé d'attribuer une zone de ce fichier à chaque développeur.
    // Théo le boss : lignes 350 à 549
    // Elven le 10E  : lignes 550 à 749
    // Développeur 3 : lignes 750 à 950
    
    // Quelques conseils pour le travail collaboratif :
    // avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer 
    // la dernière version du fichier.
    // Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 1 (Théo le boss) : lignes 350 à 549

    // --------------------------------------------------------------------------------------
    public function creerUneTrace($uneTrace) {
        // on teste si l'utilisateur existe déjà
        
        // préparation de la requête  
        if($uneTrace->getTerminee()==True){
            $textReqInsertionTraceBdd = "insert into tracegps_traces (dateDebut, dateFin, terminee, idUtilisateur)";
            $textReqInsertionTraceBdd .= " values (:dateDebut, :dateFin, 1 , :idUtilisateur)";
            $reqInsertionTraceBdd = $this->cnx->prepare($textReqInsertionTraceBdd);
            // liaison de la requête et de ses paramètres
            $reqInsertionTraceBdd->bindValue("dateDebut", utf8_decode($uneTrace->getDateHeureDebut()), PDO::PARAM_STR);
            $reqInsertionTraceBdd->bindValue("dateFin", utf8_decode($uneTrace->getDateHeureFin()), PDO::PARAM_STR);
            $reqInsertionTraceBdd->bindValue("idUtilisateur", utf8_decode($uneTrace->getIdUtilisateur()), PDO::PARAM_INT);
            // exécution de la requête
            $ok = $reqInsertionTraceBdd->execute();
        }
        else{
            $textReqInsertionTraceBdd = "insert into tracegps_traces (dateDebut, dateFin, terminee, idUtilisateur)";
            $textReqInsertionTraceBdd .= " values (:dateDebut, NULL, 0, :idUtilisateur)";
            $reqInsertionTraceBdd = $this->cnx->prepare($textReqInsertionTraceBdd);
            // liaison de la requête et de ses paramètres
            $reqInsertionTraceBdd->bindValue("dateDebut", utf8_decode($uneTrace->getDateHeureDebut()), PDO::PARAM_STR);
            $reqInsertionTraceBdd->bindValue("idUtilisateur", utf8_decode($uneTrace->getIdUtilisateur()), PDO::PARAM_INT); 
            // exécution de la requête
            $ok = $reqInsertionTraceBdd->execute();
        }
       
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        // recherche de l'identifiant (auto_increment) qui a été attribué à la trace
        $txtIdNewTrace = "Select max(id) as idMax from tracegps_traces";
        $IdNewTrace = $this->cnx->prepare($txtIdNewTrace);
        // extraction des données
        $IdNewTrace->execute();
        $uneLigne = $IdNewTrace->fetch(PDO::FETCH_OBJ);
        $unId = $uneLigne->idMax;
        $uneTrace->setId($unId);
        return true;
    }
    
    public function supprimerUneTrace($id)
    {
        $uneTrace = $this->getUneTrace($id);
        if ($uneTrace == null) 
        {
            return false;
        }
        else {            
            // suppression des traces de l'utilisateur (et des points correspondants)
            $lesPoints = $this->getLesPointsDeTrace($id);
            foreach ($lesPoints as $unPoint) 
            {
                $txt_req2 = "delete from tracegps_points" ;
                $txt_req2 .= " where id = :idPoint";
                $req2 = $this->cnx->prepare($txt_req2);
                // liaison de la requête et de ses paramètres
                $req2->bindValue("idPoint", utf8_decode($unPoint->getId()), PDO::PARAM_INT);
                // exécution de la requête
                $ok = $req2->execute();
                
            }
            
            // préparation de la requête de suppression des autorisations
            $txt_req1 = "delete from tracegps_traces" ;
            $txt_req1 .= " where id = :id";
            $req1 = $this->cnx->prepare($txt_req1);
            // liaison de la requête et de ses paramètres
            $req1->bindValue("id", utf8_decode($id), PDO::PARAM_INT);
            // exécution de la requête
            $ok = $req1->execute();
            return $ok;
        }
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 2 (LE 10E) : lignes 550 à 749
    // --------------------------------------------------------------------------------------
    
    // fournit true si le mail $adrMail existe dans la table tracegps_utilisateurs, false sinon
    // modifié par Le 10e le 16/10/2018
    public function existeAdrMailUtilisateur($adrMail) {
        // préparation de la requête de recherche
        $SelectAdrMailUtilisateur = "Select count(*) from tracegps_utilisateurs where adrMail = :adrMail";
        $req = $this->cnx->prepare($SelectAdrMailUtilisateur);
        // liaison de la requête et de ses paramètres
        $req->bindValue("adrMail", $adrMail, PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        $nbReponses = $req->fetchColumn(0);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // fourniture de la réponse
        if ($nbReponses == 0) {
            return false;
        }
        else {
            return true;
        }
    }//fin existeAdrMailUtilisateur
    
    public function getLesUtilisateursAutorisant($idUtilisateur) 
    {
        // préparation de la requête de recherche
        $IdAutorisant = "select idAutorisant from tracegps_autorisations where idAutorise = :idUtilisateur;";
        $reqIdAutorisant = $this->cnx->prepare($IdAutorisant);
        // liaison de la requête et de ses paramètres
        $reqIdAutorisant->bindValue("idUtilisateur", $idUtilisateur, PDO::PARAM_INT);
        // exécution de la requête
        $reqIdAutorisant->execute();               
        
        $uneLigneIdAutorisant = $reqIdAutorisant->fetch(PDO::FETCH_OBJ);
        
        // construction d'une collection d'objets Utilisateur
        $lesUtilisateursAutorisant = new ArrayObject();
        
        // tant qu'une ligne est trouvée :
        while ($uneLigneIdAutorisant) 
        {
            $UtilisateursAutorisant = "select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace from tracegps_vue_utilisateurs where id = :idAutorisant;";
            $reqUtilisateursAutorisant = $this->cnx->prepare($UtilisateursAutorisant);
            $reqUtilisateursAutorisant->bindValue("idAutorisant", $uneLigneIdAutorisant->idAutorisant, PDO::PARAM_INT);
            // extraction des données
            $reqUtilisateursAutorisant->execute();            
                       
            $uneLigneUtilisateursAutorisant = $reqUtilisateursAutorisant->fetch(PDO::FETCH_OBJ);     

            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigneUtilisateursAutorisant->id);
            $unPseudo = utf8_encode($uneLigneUtilisateursAutorisant->pseudo);
            $unMdpSha1 = utf8_encode($uneLigneUtilisateursAutorisant->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigneUtilisateursAutorisant->adrMail);
            $unNumTel = utf8_encode($uneLigneUtilisateursAutorisant->numTel);
            $unNiveau = utf8_encode($uneLigneUtilisateursAutorisant->niveau);
            $uneDateCreation = utf8_encode($uneLigneUtilisateursAutorisant->dateCreation);
            $unNbTraces = utf8_encode($uneLigneUtilisateursAutorisant->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigneUtilisateursAutorisant->dateDerniereTrace);
                
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateursAutorisant->append($unUtilisateur);
            
            $uneLigneIdAutorisant = $reqIdAutorisant->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $reqIdAutorisant->closeCursor();
        // fourniture de la collection
        return $lesUtilisateursAutorisant;
    }//fin getLesUtilisateursAutorisant

    public function getLesTraces($idUtilisateur)
    {
        $IdTrace = "select id from tracegps_traces where idUtilisateur =:idUtilisateurRecu order by id desc";
        $reqIdTrace = $this->cnx->prepare($IdTrace);
        $reqIdTrace->bindValue("idUtilisateurRecu", $idUtilisateur, PDO::PARAM_INT);
        // extraction des données
        $reqIdTrace->execute();
        
        $uneLigneIdTrace = $reqIdTrace->fetch(PDO::FETCH_OBJ);
        
        $lesTraces = new ArrayObject();
        
        while($uneLigneIdTrace)
        {            
            $lesTraces->append($this->getUneTrace($uneLigneIdTrace->id));
            $uneLigneIdTrace = $reqIdTrace->fetch(PDO::FETCH_OBJ);
        }
        return $lesTraces;
    }        
    
    public function getLesTracesAutorisees($idUtilisateur)
    {
        $lesUtilisateurs = $this->getLesUtilisateursAutorisant($idUtilisateur); 
        
        $lesTraces = new ArrayObject();
        $i = -1;
        foreach ($lesUtilisateurs as $unUtilisateur)
        {              
            $lesTraces->append($this->getUneTrace($unUtilisateur->getId()));
            $i++;
        }
        $lesTracesRetour = new ArrayObject();
        while($i>= 0)
        {
            $lesTracesRetour->append($lesTraces[$i]);
            $i--;
        }
        return $lesTracesRetour;
    }
    
    
    
    
    
    
    
    
 
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 3 (Mentec) : lignes 750 à 949
    // --------------------------------------------------------------------------------------
    
    
    
    //Début Création autorisation
    
    public function creerUneAutorisation ($idAutorisant, $idAutorise) {
        
        $txt_req1 = "insert into tracegps_autorisations" ;
        $txt_req1 .= " values (:idAutorisant, :idAutorise)";
        $req1 = $this->cnx->prepare($txt_req1);
        // liaison de la requête et de ses paramètres
        $req1->bindValue("idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req1->bindValue("idAutorise", $idAutorise, PDO::PARAM_INT);
        // exécution de la requête
        $ok = $req1->execute();
        

        $req1->closeCursor();
        
        return $ok;
        
    }
    
    //Fin Création autorisation
    
    
    //Début getLesUtilisateursAutorises
    
    public function getLesUtilisateursAutorises ($idUtilisateur) {
        
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs, tracegps_autorisations";
        $txt_req .= " where niveau = 1";
        $txt_req .= " and idAutorise = id AND idAutorisant = :idUtilisateur";
        $txt_req .= " order by pseudo";
        
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("idUtilisateur", utf8_decode($idUtilisateur), PDO::PARAM_INT);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la collection
        return $lesUtilisateurs;
       
    }
    
    
    //Fin getLesUtilisateursAutorises
    
    
    
    //Début supprimerUneAutorisation
    
        
    public function supprimerUneAutorisation ($idAutorisant, $idAutorise) {
        
        // préparation de la requête de recherche
        $txt_req = "DELETE from tracegps_autorisations";
        $txt_req .= " where idAutorisant = :idAutorisant";
        $txt_req .= " and idAutorise = :idAutorise";
        
        //echo $txt_req;
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("idAutorisant", utf8_decode($idAutorisant), PDO::PARAM_INT);
        $req->bindValue("idAutorise", utf8_decode($idAutorise), PDO::PARAM_INT);
        // extraction des données
        $req->execute();
        
        $req->closeCursor();
        
        $txt_req = "SELECT COUNT(*) As nb from tracegps_autorisations";
        $txt_req .= " where idAutorisant = :idAutorisant";
        $txt_req .= " and idAutorise = :idAutorise";
        
        //echo $txt_req;
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("idAutorisant", utf8_decode($idAutorisant), PDO::PARAM_INT);
        $req->bindValue("idAutorise", utf8_decode($idAutorise), PDO::PARAM_INT);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ($uneLigne->nb == 0){
            return true;
        }
        else{
            return false;
        }
        
        $req->closeCursor();
        
        
    }
    
    //Fin supprimerUneAutorisation
    
    
    //Début autoriseAConsulter
    
    
    public function autoriseAConsulter ($idAutorisant, $idAutorise) {

        $txt_req = "SELECT COUNT(*) As nb from tracegps_autorisations";
        $txt_req .= " where idAutorisant = :idAutorisant";
        $txt_req .= " and idAutorise = :idAutorise";
        
        //echo $txt_req;
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("idAutorisant", $idAutorisant, PDO::PARAM_INT);
        $req->bindValue("idAutorise", $idAutorise, PDO::PARAM_INT);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ($uneLigne->nb == 1){
            return true;
        }
        else{
            return false;
        }
        
        $req->closeCursor();
        
        
    }
    
    //Fin autoriseAConsulter
    
    //Début terminerUneTrace
    
    
    public function terminerUneTrace ($uneTrace) {
        
        $txt_req = "SELECT max(dateHeure) as dateHeure from tracegps_points where idTrace = :uneTrace";

        
        //echo $txt_req;
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue("uneTrace", $uneTrace, PDO::PARAM_INT);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ($uneLigne->dateHeure == NULL)
        {
            $dateHeureFin = date("Y-m-d H-i-s");
        }
        else 
        {
            $dateHeureFin = $uneLigne->dateHeure;
        }
        
        $text_req = "UPDATE tracegps_traces SET dateFin = :datHeureFin, terminee = 1 WHERE id = :uneTrace";
        $req = $this->cnx->prepare($text_req);
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        $req->bindValue("datHeureFin", $dateHeureFin, PDO::PARAM_STR);
        $req->bindValue("uneTrace", $uneTrace, PDO::PARAM_INT);
        
        //echo "UPDATE tracegps_traces SET dateFin = '".$dateHeureFin.", terminee = 1 WHERE id = ".$uneTrace;
        
        $ok = $req->execute();
        if ($ok){
            return true;
        }
        else{
            return false;
        }
        
        $req->closeCursor();
        
    }
    
    //Fin terminerUneTrace
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
   
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 4 (Derrien) : lignes 950 à 1150
    // --------------------------------------------------------------------------------------
    
    
    
    public function getLesPointsDeTrace($idTrace) {
        // préparation de la requête de recherche
        $txt_req = "Select idTrace, id, latitude, longitude, altitude, dateHeure, rythmeCardio";
        $txt_req .= " from tracegps_points";
        $txt_req .= " where idTrace = :id";
        $txt_req .= " order by id";
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue('id', $idTrace, PDO::PARAM_INT);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
              
        // construction d'une collection d'objets PointDeTrace
        $uneTrace = array();
        // tant qu'une ligne est trouvée :
        while ($uneLigne) {
            // création d'un objet PointDeTrace
            $unIdTrace = $uneLigne->idTrace;
            $unId = $uneLigne->id;
            $uneLatitude = $uneLigne->latitude;
            $uneLongitude = $uneLigne->longitude;
            $uneAltitude = $uneLigne->altitude;
            $uneDateHeure = $uneLigne->dateHeure;
            $unRythmeCardio = $uneLigne->rythmeCardio;
            
            $unTempsCumule = 0;
            $uneDistanceCumuluee = 0;
            $uneVitesse = 0;
            
            $unPointDeTrace = new PointDeTrace($unIdTrace, $unId, $uneLatitude, $uneLongitude, $uneAltitude, $uneDateHeure, $unRythmeCardio, $unTempsCumule, $uneDistanceCumuluee, $uneVitesse);
            // ajout de l'utilisateur à la collection
            $uneTrace[] = $unPointDeTrace;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la collection
        return $uneTrace;
    }
    
    public function getUneTrace($idTrace)
    {
        $txt_req = "SELECT id, dateDebut, dateFin, terminee, idUtilisateur";
        $txt_req .= " FROM tracegps_traces";
        $txt_req .= " WHERE id = :idTraces";
        
        $req = $this->cnx->prepare($txt_req);
        $req->bindValue('idTraces', $idTrace, PDO::PARAM_INT);
        $req->execute();
        
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);

        $retour = null;
       
        while($uneLigne)
        {
            $laTrace = new Trace($uneLigne->id, $uneLigne->dateDebut, $uneLigne->dateFin, $uneLigne->terminee, $uneLigne->idUtilisateur);
            $lesPoints = DAO::getLesPointsDeTrace($uneLigne->id);
            
            foreach ($lesPoints as $unPoint)
            {   
                $laTrace->ajouterPoint($unPoint);
            }
            
            $retour = $laTrace;
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        
        return $retour;
        
    }
    
    
    public function creerUnPointDeTrace($unPointDeTrace)
    {

        
        $idTrace = $unPointDeTrace->getIdTrace();
        $id = $unPointDeTrace->getId();
        $latitude = $unPointDeTrace->getLatitude();
        $longitude = $unPointDeTrace->getLongitude();
        $altitude = $unPointDeTrace->getAltitude();
        $dateHeure = $unPointDeTrace->getDateHeure();
        $rythmeCardio = $unPointDeTrace->getRythmeCardio();
        
        $req_txt = "INSERT INTO tracegps_points";
        $req_txt .= " VALUES (:idTrace, :id, :latitude, :longitude, :altitude, :dateHeure, :rythmeCardio);";
        $reqInsert = $this->cnx->prepare($req_txt);
        $reqInsert->bindValue("idTrace",$idTrace,PDO::PARAM_INT);
        $reqInsert->bindValue("id", $id, PDO::PARAM_INT);
        $reqInsert->bindValue("latitude", $latitude, PDO::PARAM_STR);
        $reqInsert->bindValue("longitude", $longitude, PDO::PARAM_STR);
        $reqInsert->bindValue("altitude", $altitude, PDO::PARAM_STR);
        $reqInsert->bindValue("dateHeure", $dateHeure, PDO::PARAM_STR);
        $reqInsert->bindValue("rythmeCardio", $rythmeCardio, PDO::PARAM_INT);

        $ok = $reqInsert->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        if ($id == 1)
        {
            $req_txt = "UPDATE tracegps_traces SET dateDebut = :dateDebut";
            $reqUpdate = $this->cnx->prepare($req_txt);
            $reqUpdate->bindValue("dateDebut", $dateHeure, PDO::PARAM_STR);
            $reqUpdate->execute();
        }
        
        return true;
    }
    public function getToutesLesTraces()
    {
        // préparation de la requête de recherche
        $txt_req = "Select id";
        $txt_req .= " from tracegps_traces ORDER BY id DESC";
        
        $req = $this->cnx->prepare($txt_req);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        $lesTraces = array();
        while($uneLigne)
        {
            $uneTrace = DAO::getUneTrace($uneLigne->id);
            
            $lesTraces[] = $uneTrace;
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
            
        }
        return $lesTraces;
    }  
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
} // fin de la classe DAO

// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!