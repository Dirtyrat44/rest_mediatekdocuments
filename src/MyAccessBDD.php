<?php

use function PHPSTORM_META\type;

include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD
{

    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */
    protected function traitementSelect(string $table, ?array $champs): ?array
    {
        switch ($table) {
            case "livre":
                return $this->selectAllLivres();
            case "dvd":
                return $this->selectAllDvd();
            case "revue":
                return $this->selectAllRevues();
            case "exemplaire":
                return $this->selectExemplairesRevue($champs);
            case "commandedocument":
                return $this->selectCommande($champs, $table);
            case "abonnement":
                return $this->selectCommande($champs, $table);
            case "genre":
            case "public":
            case "rayon":
            case "etat":
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * Demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "livre":
                return $this->insertDocumentWithType($champs, $table, [
                    "id" => $champs["id"],
                    "ISBN" => $champs["ISBN"],
                    "auteur" => $champs["auteur"],
                    "collection" => $champs["collection"]
                ]);
            case "dvd":
                return $this->insertDocumentWithType($champs, $table, [
                    "id" => $champs["id"],
                    "synopsis" => $champs["synopsis"],
                    "realisateur" => $champs["realisateur"],
                    "duree" => $champs["duree"]
                ]);
            case "revue":
                return $this->insertDocumentWithType($champs, $table, [
                    "id" => $champs["id"],
                    "periodicite" => $champs["periodicite"],
                    "delaiMiseADispo" => $champs["delaiMiseADispo"]
                ]);
            case "commandedocument":
                return $this->insertCommande($champs, $table);
            case "abonnement":
                return $this->insertCommande($champs, $table);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);
        }
    }

    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int
    {
        switch ($table) {
            case "livre":
                return $this->updateDocumentWithType($champs, $table, [
                    "id" => $champs["id"],
                    "ISBN" => $champs["ISBN"],
                    "auteur" => $champs["auteur"],
                    "collection" => $champs["collection"]
                ]);
            case "dvd":
                return $this->updateDocumentWithType($champs, $table, [
                    "id" => $champs["id"],
                    "synopsis" => $champs["synopsis"],
                    "realisateur" => $champs["realisateur"],
                    "duree" => $champs["duree"]
                ]);
            case "revue":
                return $this->updateDocumentWithType($champs, $table, [
                    "id" => $champs["id"],
                    "periodicite" => $champs["periodicite"],
                    "delaiMiseADispo" => $champs["delaiMiseADispo"]
                ]);
            case "commandedocument":
                return $this->updateCommande($champs);
            case "exemplaire":
                return $this->updateExemplaire($champs);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "livre":
                return $this->deleteDocumentWithType($champs, $table);
            case "dvd":
                return $this->deleteDocumentWithType($champs, $table);
            case "revue":
                return $this->deleteDocumentWithType($champs, $table);
            case "commandedocument":
                return $this->deleteCommande($champs, $table);
            case "abonnement":
                return $this->deleteCommande($champs, $table);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs): ?array
    {
        if (empty($champs)) {
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        } else {
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertOneTupleOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value) {
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ") values (";
        foreach ($champs as $key => $value) {
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        if (is_null($id)) {
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value) {
            if ($key !== "id") {
                $requete .= "$key=:$key,";
            }
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete) - 5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table): ?array
    {
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres(): ?array
    {
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd(): ?array
    {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues(): ?array
    {
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * 
     * @param string $type
     * @param array $champs
     * @return array|null
     */
    private function selectCommande(?array $champs, string $type): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];

        switch ($type) {
            case "commandedocument":
                $requete = "SELECT cd.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, s.libelle ";
                $requete .= "FROM commandedocument cd JOIN commande c ON c.id = cd.id JOIN suivi s ON s.id = cd.idSuivi ";
                $requete .= "WHERE cd.idLivreDvd = :id ";
                $requete .= "ORDER BY c.dateCommande DESC";
                break;
            case "abonnement":
                $requete = "SELECT a.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue ";
                $requete .= "FROM abonnement a ";
                $requete .= "JOIN commande c ON c.id = a.id ";
                $requete .= "WHERE a.idRevue = :id ";
                $requete .= "ORDER BY c.dateCommande DESC";
                break;
            default:
                return null;
        }
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Vérifie si un document as une commande ou un abonnement
     *
     * @param array $id du document
     * @param string $type de document (livre, dvd, revue)
     * @return bool true si au moins 1 ligne sinon false
     */
    private function documentHasCommande(array $id, string $type): bool
    {
        $requete = "";
        if ($type !== "revue") {
            $requete = "SELECT COUNT(*) as nb FROM commandedocument cd ";
            $requete .= "JOIN livres_dvd ld ON cd.idLivreDvd = ld.id ";
            $requete .= "WHERE ld.id = :id";
        } else {
            $requete = "SELECT COUNT(*) as nb FROM abonnement a ";
            $requete .= "JOIN revue r ON a.idRevue = r.id ";
            $requete .= "WHERE r.id = :id";
        }

        $reponse = $this->conn->queryBDD($requete, ["id" => $id["id"]]);
        if ($reponse === null || !isset($reponse[0]["nb"])) {
            return false;
        }

        return $reponse[0]["nb"] > 0;
    }

    /**
     * Vérifie si un document possède un ou plusieurs exemplaires
     *
     * @param array $id du document
     * @return bool true si au moins 1 exemplaire sinon false
     */
    private function documentHasExemplaire(array $id): bool
    {
        $requete = "";
        $requete = "SELECT COUNT(*) as nb FROM exemplaire e ";
        $requete .= "JOIN document d ON e.id = d.id ";
        $requete .= "WHERE d.id = :id";

        $reponse = $this->conn->queryBDD($requete, ["id" => $id["id"]]);
        if ($reponse === null || !isset($reponse[0]["nb"])) {
            return false;
        }

        return $reponse[0]["nb"] > 0;
    }

    /**
     * Ajoute un enregistrement dans la table document
     *
     * @param array $data Tableau associatif contenant les champs nécessaires
     * @return bool true si l'insertion réussi false sinon
     */
    private function insertDocument(array $data): bool
    {
        return $this->insertOneTupleOneTable("document", [
            "id" => $data["id"],
            "titre" => $data["titre"],
            "image" => $data["image"],
            "idGenre" => $data["idGenre"],
            "idPublic" => $data["idPublic"],
            "idRayon" => $data["idRayon"]
        ]) !== null;
    }

    /**
     * Met à jour un document dans la table `document`
     * @param array $data du document
     * @return bool true si la requête a été exécutée sans erreur, false sinon
     */
    private function updateDocument(array $data): bool
    {
        return $this->updateOneTupleOneTable("document", $data["id"], [
            "id" => $data["id"],
            "titre" => $data["titre"],
            "image" => $data["image"],
            "idGenre" => $data["idGenre"],
            "idPublic" => $data["idPublic"],
            "idRayon" => $data["idRayon"]
        ]) !== null;
    }

    /**
     * Ajoute un document en fonction de son type(livre, DVD, revue)
     * La méthode effectue une insertion transactionnelle dans :
     * La table document
     * La table spécifique du document
     * La table Livre_dvd (uniquement pour livre ou DVD)
     * @param array $data Données du document
     * @param string $type Type du document
     * @param array $typeData Données spécifiques au type de document
     * @return int|null Retourne 1 si l’insertion réussit ou null si échec
     */
    private function insertDocumentWithType(array $data, string $type, array $typeData): ?int
    {
        try {
            $this->conn->beginTransaction();

            if (!$this->insertDocument($data)) {
                throw new Exception("Echec insert document");
            }

            if ($type !== "revue" && !$this->insertOneTupleOneTable("livres_dvd", ["id" => $data["id"]])) {
                throw new Exception("Echec insert livres_dvd");
            }

            if (!$this->insertOneTupleOneTable($type, $typeData)) {
                throw new Exception("Echec insert $type");
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * Met à jour un document et ses données spécifiques (livre, dvd ou revue)
     * Effectue la mise à jour dans `document` et dans la ou les table liée
     * @param array $data du document
     * @param string $type du document
     * @param array $typeData spécifique liée au type
     * @return int|null Retourne 1 si modification réussit, null en cas d'erreur
     */
    private function updateDocumentWithType(array $data, string $type, array $typeData): ?int
    {
        try {
            $this->conn->beginTransaction();

            if (!$this->updateDocument($data)) {
                throw new Exception("Echec update document");
            }

            $result = $this->updateOneTupleOneTable($type, $data["id"], $typeData);
            if ($result === null) // On évite de renvoyer exception pour 0 modif
            {
                throw new Exception("Echec update $type");
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * Supprime un document et ses données liées
     * Supprime de la table spécifique au type, de livres_dvd si c'est un livre ou un dvd et de document
     * @param array $data Doit contenir l'id du document à supprimer
     * @param string $type du document (livre, dvd, revue)
     * @return int|null Retourne 1 si la suppression réussit, null en cas d'erreur
     */
    private function deleteDocumentWithType(array $data, string $type): ?int
    {
        try {
            if ($this->documentHasCommande($data, $type) || $this->documentHasExemplaire($data)) {
                throw new UserMessageException("Ce document possède déjà une commande ou un exemplaire");
            }
            $this->conn->beginTransaction();

            if (!$this->deleteTuplesOneTable($type, ["id" => $data["id"]])) {
                throw new Exception("Echec suppression $type");
            }

            if ($type !== "revue" && !$this->deleteTuplesOneTable("livres_dvd", ["id" => $data["id"]])) {
                throw new Exception("Echec suppression livres_dvd");
            }

            if (!$this->deleteTuplesOneTable("document", ["id" => $data["id"]])) {
                throw new Exception("Echec suppression document");
            }

            $this->conn->commit();
            return 1;
        } catch (userMessageException $ex) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $ex;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Génère le prochain id de commande au format "00001"
     * Cherche le plus grand et l'incrémente de 1
     * @return string
     */
    private function genererIdCommande(): string
    {
        $requete = "SELECT LPAD(COALESCE(MAX(CAST(id AS UNSIGNED)),0)+1,5,'0') AS next ";
        $requete .= "FROM commande;";
        $res = $this->conn->queryBDD($requete);
        return $res[0]["next"];
    }

    /**
     * Vérifie si une commande est supprimable
     * @param string $id de la commande
     * @param string $type commandedocument ou abonnement
     * @return bool True si la ligne existe et que le bon statut correspond, false interdit, null introuvable
     */
    private function commandeSupprimable(string $id, string $type): bool|null
    {
        if ($type === "commandedocument") {
            $requete = "SELECT idSuivi ";
            $requete .= "FROM commandedocument ";
            $requete .= "WHERE id = :id;";

            $result = $this->conn->queryBDD($requete, ["id" => $id]);
            if ($result === null || !isset($result[0]["idSuivi"])) {
                return null;
            }
            return $result[0]["idSuivi"] < '00003'; // Si statut < livrée
        } elseif ($type === "abonnement") {
            $requete = "SELECT c.dateCommande, a.dateFinAbonnement, a.idRevue ";
            $requete .= "FROM abonnement a ";
            $requete .= "JOIN commande c ON c.id = a.id ";
            $requete .= "WHERE a.id = :id";

            $result = $this->conn->queryBDD($requete, ["id" => $id])[0] ?? null;

            if ($result === null || !isset($result["idRevue"])) {
                return null;
            }

            // Count si un des exemplaire trouvés est en cours d'abonnement
            $reqCount = "SELECT COUNT(*) AS nb ";
            $reqCount .= "FROM exemplaire e ";
            $reqCount .= "WHERE id = :idRevue ";
            $reqCount .= "AND e.dateAchat BETWEEN :dateCommande AND :dateFinAbonnement;";

            $param = [
                "idRevue" => $result["idRevue"],
                "dateCommande"      => $result["dateCommande"],
                "dateFinAbonnement"      => $result["dateFinAbonnement"]
            ];
            $nb = $this->conn->queryBDD($reqCount, $param)[0]["nb"] ?? 0;

            // true si aucun exemplaires
            return $nb === 0;
        } else {
            throw new Exception("Type $type inconnu pour commandeSupprimable");
        }
    }

    /**
     * Ajoute une commande ou un abonnement
     * La méthode effectue une insertion transactionnelle dans :
     * La table commande
     * La table commandedocument ou la table abonnement
     * 
     * @param array $data de la commande
     * @param string $type commande ou abonnement
     * @return int|null Retourne 1 si l’insertion réussit ou null si échec
     */
    private function insertCommande(array $data, string $type): ?int
    {
        try {
            $id = $this->genererIdCommande();

            $this->conn->beginTransaction();

            if (!$this->insertOneTupleOneTable("commande", [
                "id" => "$id",
                "dateCommande" => $data["dateCommande"],
                "montant" => $data["montant"]
            ])) {
                throw new Exception("Echec insert commande");
            }

            if ($type !== "commandedocument" && !$this->insertOneTupleOneTable("abonnement", [
                "id" => $id,
                "dateFinAbonnement" => $data["dateFinAbonnement"],
                "idRevue" => $data["idRevue"]
            ])) {
                throw new Exception("Echec insert abonnement");
            }

            if ($type !== "abonnement" && !$this->insertOneTupleOneTable("commandedocument", [
                "id" => "$id",
                "nbExemplaire" => $data["nbExemplaire"],
                "idLivreDvd" => $data["idLivreDvd"]
            ])) {
                throw new Exception("Echec insert commandedocument");
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * Supprime une commande si son statut est < '00003'
     *
     * @param array $data
     * @param string $type
     * @return int|null Retourne 1 si la suppression réussit ou null si échec
     */
    private function deleteCommande(array $data, string $type): ?int
    {
        try {
            $id   = $data["id"] ?? null;
            $suppr = $this->commandeSupprimable($id, $type);

            if ($suppr === null) {
                throw new UserMessageException("Commande introuvable ou n'existe pas");
            }
            if ($suppr === false) {
                throw new UserMessageException($type === "abonnement" ? "Suppression impossible : un ou plusieurs exemplaires sont rattachés." : "Suppression impossible : commande livrée ou réglée.");
            }

            $this->conn->beginTransaction();

            if ($type === "commandedocument" && !$this->deleteTuplesOneTable("commandedocument", ["id" => $id])) {
                throw new Exception("Echec suppression commandedocument");
            }

            if ($type === "abonnement" && !$this->deleteTuplesOneTable("abonnement", ["id" => $id])) {
                throw new Exception("Echec suppression abonnement");
            }

            if (!$this->deleteTuplesOneTable("commande", ["id" => $id])) {
                throw new Exception("Echec suppression commande");
            }

            $this->conn->commit();
            return 1;
        } catch (UserMessageException $ex) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $ex;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return null;
        }
    }

    /**
     * Modifie une commande si son statut est < '00003'
     *
     * @param array $data
     * @return int|null Retourne 1 si la modification réussit ou null si échec
     */
    private function updateCommande(array $data): ?int
    {
        try {
            $result = $this->updateOneTupleOneTable("commandedocument", $data["id"], [
                "idSuivi" => $data["idSuivi"]
            ]);

            if ($result === null) {
                throw new UserMessageException("Modification impossible : commande Livrée ou Réglée.");
            }

            if ($result === 0) {
                $exist = $this->conn->queryBDD("SELECT 1 FROM commandedocument WHERE id = :id", ["id" => $data["id"]]);
                if (empty($exist)) {
                    throw new UserMessageException("Commande introuvable");
                }

                return 1; // Aucune modification
            }

            return 1;
        } catch (UserMessageException $ex) {
            throw $ex;
        }
    }

    private function updateExemplaire(array $data): ?int
    {
        try {
            $this->conn->beginTransaction();
            
            $param = [
                "id" => $data["id"],
                "numero" => $data["numero"],
                "idEtat" => $data["idEtat"]
            ];
            
            $requete = "UPDATE exemplaire ";
            $requete .= "SET idEtat = :idEtat ";
            $requete .= "WHERE id = :id AND numero = :numero";
            
            $result = $this->conn->updateBDD($requete, $param);
    
            if ($result === null)
            {
                throw new Exception("Erreur lors de la mise à jour");
            }
    
            $this->conn->commit();
            return 1;
    
        } catch (Exception $e) {
            $this->conn->rollBack();
            
            return null;
        }
    }
}
