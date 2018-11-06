<?php
// Projet TraceGPS
// fichier : modele/Trace.class.php
// Rôle : la classe Trace représente une trace ou un parcours
// Dernière mise à jour : 17/7/2018 par JM CARTRON

include_once ('PointDeTrace.class.php');

class Trace
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $id;				// identifiant de la trace
    private $dateHeureDebut;		// date et heure de début
    private $dateHeureFin;		// date et heure de fin
    private $terminee;			// true si la trace est terminée, false sinon
    private $idUtilisateur;		// identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace;		// la collection (array) des objets PointDeTrace formant la trace
    
    public function Trace($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur) {
        // A VOUS DE TROUVER LE CODE  MANQUANT
        $this->id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;
        $this->lesPointsDeTrace = array();
    }
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}
    
    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}
    
    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}
    
    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}
    
    public function getIdUtilisateur() {return $this->idUtilisateur;}
    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}
    
    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}
    
    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
            $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
            $msg .= "Terminée : Oui  <br>";
        }
        else {
            $msg .= "Terminée : Non  <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) {
            if ($this->getDateHeureFin() != null) {
                $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
            }
            $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
            $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
            $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
            $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
            $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
            $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
            $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
            $msg .= "Centre du parcours : " . "<br>";
            $msg .= "   - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
            $msg .= "   - Longitude : "  . $this->getCentre()->getLongitude() . "<br>";
            $msg .= "   - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }
    
    public function getNombrePoints()
    {
        $nbrePoints = sizeof($this->lesPointsDeTrace);
        return $nbrePoints;
    }
    
    public function getCentre()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return null;
        $unPoint = $this->lesPointsDeTrace[0];
        $latMax = $unPoint->getLatitude();
        $latMin = $unPoint->getLatitude();
        $lonMax = $unPoint->getLongitude();
        $lonMin = $unPoint->getLongitude();
        
        for ($i = 0; $i < sizeof($this->lesPointsDeTrace) - 1; $i++) 
        {
            $lePoint = $this->lesPointsDeTrace[$i];
            $lat = $lePoint->getLatitude();
            $lon = $lePoint->getLongitude();
            if($latMax < $lat) $latMax = $lat;
            if($latMin > $lat) $latMin = $lat;
            if($lonMax < $lon) $lonMax = $lon;
            if($lonMin > $lon) $lonMin = $lon;

        }
        $latMoy = ($latMax + $latMin)/2;
        $lonMoy = ($lonMax + $lonMin)/2;
        
        $point = new Point($latMoy, $lonMoy, 0);
        return $point;
    }
    
    public function getDenivele()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return 0;
        $unPoint = $this->lesPointsDeTrace[0];
        $altMax = $unPoint->getAltitude();
        $altMin = $unPoint->getAltitude();
        
        for ($i = 0; $i < sizeof($this->lesPointsDeTrace); $i++)
        {
            $lePoint = $this->lesPointsDeTrace[$i];
            $alt = $lePoint->getAltitude();
            if($altMax < $alt) $altMax = $alt;
            if($altMin > $alt) $altMin = $alt;
        }
        
        $denivele = $altMax-$altMin;
        return $denivele;
    }
    
    public function getDureeEnSecondes()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        $unPoint = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace)-1];
        return $unPoint->getTempsCumule();
    }
    
    public function getDureeTotale()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return "00:00:00";
        $temps = $this->getDureeEnSecondes();
        $heures = (int)$temps/3600;
        $temps = $temps%3600;
        $minutes = (int)$temps/60;
        $secondes = $temps%60;
        return sprintf("%02d",$heures) . ":" . sprintf("%02d",$minutes) . ":" . sprintf("%02d",$secondes);
    }
    
    public function getDistanceTotale()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return 0;
        $unPoint = $this->lesPointsDeTrace[sizeof($this->lesPointsDeTrace)-1];
        
        return $unPoint->getDistanceCumulee();
    }
    
    public function getDenivelePositif()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        $denivelePositif = 0;
        $denivele = 0;
        
        for ($i = 0; $i < sizeof($this->lesPointsDeTrace) - 1; $i++)
        {
            $lePointPrecedent = $this->lesPointsDeTrace[$i];
            $lePointSuivant  = $this->lesPointsDeTrace[$i+1];
            $denivele = $lePointSuivant->getAltitude()-$lePointPrecedent->getAltitude();
            if($denivele > 0) $denivelePositif += $denivele;
        }
        return $denivelePositif;
    }
    
    public function getDeniveleNegatif()
    {
        if(sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        $deniveleNegatif = 0;
        $denivele = 0;
        
        for ($i = 0; $i < sizeof($this->lesPointsDeTrace) - 1; $i++)
        {
            $lePointPrecedent = $this->lesPointsDeTrace[$i];
            $lePointSuivant  = $this->lesPointsDeTrace[$i+1];
            $denivele = $lePointSuivant->getAltitude()-$lePointPrecedent->getAltitude();
            if($denivele < 0) $deniveleNegatif += $denivele*-1;
        }
        return $deniveleNegatif;
    }
    
    public function getVitesseMoyenne()
    {
        if($this->getDistanceTotale() == 0) return 0;
        $distance = $this->getDistanceTotale();
        $duree = $this->getDureeEnSecondes();
        
        $vitesse = $distance / (double) ($duree/3600);
        return $vitesse;
    }
    
    public function ajouterPoint(PointDeTrace $unPoint)
    {
        if(sizeof($this->lesPointsDeTrace) == 0)
        {
            $unPoint->setDistanceCumulee(0);
            $unPoint->setTempsCumule(0);
            $unPoint->setVitesse(0);
        }
        else
        {
            $leDernierPoint = $this->lesPointsDeTrace[(sizeof($this->lesPointsDeTrace))-1];
            
            $distance = Point::getDistance($leDernierPoint, $unPoint);
            $distanceCumulee = $leDernierPoint->getDistanceCumulee() + $distance;
            
            $temps = strtotime($unPoint->getDateHeure()) - strtotime($leDernierPoint->getDateHeure());
            $tempsCumule = $leDernierPoint->getTempsCumule() + $temps;
            
            $vitesse = $distance/(double)($temps/3600);
            
            $unPoint->setDistanceCumulee($distanceCumulee);
            $unPoint->setTempsCumule($tempsCumule);
            $unPoint->setVitesse($vitesse);
        }
        $this->lesPointsDeTrace[] = $unPoint;
    }
    
    public function viderListePoints()
    {
        unset($this->lesPointsDeTrace);
    }
    
    
        
    
    
    
} // fin de la classe Trace

// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!
