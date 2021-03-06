<?php

class DbHandler
{
    private $connection;

    function __construct()
    {
        // open database connection
        $this->connection = Database::getInstance();
    }

    function __destruct()
    {
        $this->connection = null;
    }

    /* ------------------------------Validation--------------------------------------- */

    /**
     * Fetching Account by email
     *
     * @param String $email
     *            Account email
     */
    public function getAccountByEmail($email)
    {
        $STH = $this->connection->prepare("SELECT idVenues, name, email, urlPhoto, idAccount, a.idAddress, state, city, zip, address_1, address_2
        FROM Venues v
        INNER JOIN Address a
        ON v.idAddress = a.idAddress
        WHERE email = :email");
        $STH->bindParam(':email', $email);

        if ($STH->execute()) {

            $account = $STH->fetch();
            return $account;
        } else {
            return NULL;
        }
    }
    /* ------------------------------Validation--------------------------------------- */

    /* -------------------------------Account----------------------------------------- */
    /**
     * Creating new Account
     *
     * @param String $email
     *            account login email
     * @param String $password
     *            account login password
     * @param String $confirmCode
     *            confirmation code
     * @param String $name
     *            Venue name
     * @param String $addressFirst
     *            address 1
     * @param String $addressSecond
     *            address 2
     * @param String $city
     *            city name
     * @param String $country
     *            country name
     * @param String $state
     *            state name
     * @param String $zipCode
     *            zip code
     * @param String $urlImage
     *            logo image
     *
     * @return constant ('ACCOUNT_CREATED_SUCCESSFULLY', 'ACCOUNT_CREATE_FAILED', 'ACCOUNT_ALREADY_EXIST')
     */
    public function createAccount($email, $password, $confirmCode, $name, $addressFirst, $addressSecond, $city, $country, $state, $zipCode, $urlImage, $idVenue, $idAddress)
    {
        // First check if account already exist in db
        if (!$this->isAccountInDb($email)) {
            $idAccount = SoapHandler::register($confirmCode, $email, $password);
            if ($idAccount == ACCOUNT_CREATE_FAILED) return ACCOUNT_CREATE_FAILED;

            if ($idVenue == NULL) {
                $STH = $this->connection->prepare("BEGIN;
					INSERT INTO Address(state, city, zip, address_1, address_2)
					VALUES(:state, :city, :zip, :address_1, :address_2);
					INSERT INTO Venues(name, email, urlPhoto, idAddress, idAccount)
					VALUES(:name, :email, :urlPhoto, LAST_INSERT_ID(), :idAccount);
					COMMIT;");
            } else {
                $STH = $this->connection->prepare("BEGIN; UPDATE Address
                SET
                state = :state, city = :city, zip =  :zip, address_1 = :address_1, address_2 =  :address_2
                WHERE idAddress = :idAddress;
                UPDATE Venues
                SET
                name = :name, urlPhoto = :urlPhoto, idAddress = :idAddress, idAccount = :idAccount
                WHERE idVenues = :idVenue;
                COMMIT;");
                $STH->bindParam(':idAddress', $idAddress);
                $STH->bindParam(':idVenue', $idVenue);
            }
            $STH->bindParam(':state', $state);
            $STH->bindParam(':city', $city);
            $STH->bindParam(':zip', $zipCode);
            $STH->bindParam(':address_1', $addressFirst);
            $STH->bindParam(':address_2', $addressSecond);
            $STH->bindParam(':name', $name);
            $STH->bindParam(':email', $email);
            $STH->bindParam(':urlPhoto', $urlImage);
            $STH->bindParam(':idAccount', $idAccount);

            $result = $STH->execute();

            // Check for successful insertion
            if ($result) {
                // Venue successfully inserted
                return ACCOUNT_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create Venue
                return ACCOUNT_CREATE_FAILED;
            }
        } else {
            // Venue with same email already existed in the db
            return ACCOUNT_ALREADY_EXIST;
        }
    }

    /**
     * Checking for duplicate account by email address
     *
     * @param String $email
     *            email to check in db
     * @return boolean
     */
    private
    function isAccountInDb($email)
    {
        $STH = $this->connection->prepare("SELECT idAccount from Venues WHERE email = :email");
        $STH->bindParam(':email', $email);
        $STH->execute();
        $idAccount = $STH->fetch()['idAccount'];

        return $idAccount != NULL;
    }

    /**
     * Fetching Account id by api key
     *
     * @param String $apiKey
     *            Account api key
     */
    public function getVenueId($idAccount)
    {
        $STH = $this->connection->prepare("SELECT idVenues FROM Venues WHERE idAccount=:idAccount");
        $STH->bindParam(':idAccount', $idAccount);
        if ($STH->execute()) {
            $idVenue = $STH->fetch()['idVenues'];
            return $idVenue;
        } else {
            return NULL;
        }

        //$venue ["idVenue"] = 3;
        // return $venue;
    }

    public function geVenueByEmail($email)
    {
        $STH = $this->connection->prepare("SELECT * FROM accounts WHERE email=:email");
        $STH->bindParam(':email', $email);
        if ($STH->execute()) {
            $account = $STH->fetch();
            return $account;
        } else {
            return NULL;
        }
    }

    public function logout($apiKey)
    {
        $STH = $this->connection->prepare("DELETE FROM Accounts WHERE apiKey=:apiKey");
        $STH->bindParam(':apiKey', $apiKey);
        if ($STH->execute()) {
            return TRUE;
        } else {
            return FALSEß;
        }
    }

    /* -------------------------------Account----------------------------------------- */
    /* -------------------------------Events------------------------------------------ */
    public function createEvent($idVenue, $name, $detail, $entry, $imgUrl, $date, $time, $status, $visible, $notes, $performerEmail, $performerPhoneNumber, $youtubeVideo, $category)
    {
        $STH = $this->connection->prepare("INSERT INTO Events(name, details, entry, imgUrl, date, time, status, visible, idVenue, note, performerEmail, performerPhoneNumber, youtubeVideo)
				VALUES(:name,:details, :entry, :imgUrl, :date, :time, :status, :visible, :idVenue, :notes, :performerEmail, :performerPhoneNumber, :youtubeVideo)");

        $STH->bindParam(':name', $name);
        $STH->bindParam(':details', $detail);
        $STH->bindParam(':entry', $entry);
        $STH->bindParam(':imgUrl', $imgUrl);
        $STH->bindParam(':date', $date);
        $STH->bindParam(':time', $time);
        $STH->bindParam(':status', $status);
        $STH->bindParam(':visible', $visible);
        $STH->bindParam(':idVenue', $idVenue);
        $STH->bindParam(':notes', $notes);
        $STH->bindParam(':performerEmail', $performerEmail);
        $STH->bindParam(':performerPhoneNumber', $performerPhoneNumber);
        $STH->bindParam(':youtubeVideo', $youtubeVideo);

        if ($STH->execute()) {
            // event created
            $eventId = $this->connection->lastInsertId();
            if ($this->assignCategory($eventId, $category))
                return $eventId; // returns ID of event
            return NULL;
        } else {
            // event failed to create
            return NULL;
        }
    }

    public
    function getAllEvents()
    {
        $STH = $this->connection->prepare("SELECT idEvents, name, date, details, entry, imgUrl, time, status, visible,  note,
            performerEmail, performerPhoneNumber, youtubeVideo, (SELECT COUNT(*) FROM ViewCounter WHERE e.idEvents = idEvent) as views
            FROM Events e  WHERE idVenue = :idVenue;");
        $STH->bindParam(':idVenue', Validation::$idVenue);
        $STH->execute();

        $events = $STH->fetchAll();

        return $events;
    }

    public function getAllEventsInMonth($month)
    {
        $STH = $this->connection->prepare("SELECT idEvents as id, v.idVenues as venueId, e.name as eventName, date, time,
            e.visible, e.details, e.entry, e.imgUrl, youtubeVideo, v.name as venueName,
            v.email as venueEmail, v.urlPhoto, a.address_1, a.city, a.state, a.zip
            FROM Events e
            INNER JOIN Venues v
            ON e.idVenue = v.idVenues
            INNER JOIN Address a
            ON a.idAddress = v.idAddress
            WHERE visible = 1
            AND MONTH(date) = :month
            AND YEAR(date) = YEAR(CURDATE())
            ORDER BY date, time;");
        $STH->bindParam(':month', $month);
        $STH->execute();

        $events = $STH->fetchAll();

        return $events;
    }

    public function getAllVenueEvents($idVenue, $month)
    {
        $STH = $this->connection->prepare("SELECT idEvents as id, v.idVenues as venueId, e.name as eventName, date, time,
            e.visible, e.details, e.entry, e.imgUrl, youtubeVideo, v.name as venueName,
            v.email as venueEmail, v.urlPhoto, a.address_1, a.city, a.state, a.zip
            FROM Events e
            INNER JOIN Venues v
            ON e.idVenue = v.idVenues
            INNER JOIN Address a
            ON a.idAddress = v.idAddress
            WHERE visible = 1
            AND v.idVenues = :idVenue
            AND MONTH(date) = :month
            AND YEAR(date) = YEAR(CURDATE())
            ORDER BY date, time;");
        $STH->bindValue(':idVenue', $idVenue);
        $STH->bindValue(':month', $month);
        $STH->execute();
        $events = $STH->fetchAll();

        return $events;
    }

    public function getCityEvents($city, $month)
    {
        $STH = $this->connection->prepare("SELECT idEvents as id, v.idVenues as venueId, e.name as eventName, date, time,
            e.visible, e.details, e.entry, e.imgUrl, youtubeVideo, v.name as venueName,
            v.email as venueEmail, v.urlPhoto, a.address_1, a.city, a.state, a.zip
            FROM Events e
            INNER JOIN Venues v
            ON e.idVenue = v.idVenues
            INNER JOIN Address a
            ON a.idAddress = v.idAddress
            WHERE visible = 1
            AND LOWER(a.city) = LOWER(:city)
            AND MONTH(date) = :month
            AND YEAR(date) = YEAR(CURDATE())
            ORDER BY date, time;");
        $STH->bindValue(':city', $city);
        $STH->bindValue(':month', $month);
        $STH->execute();
        $events = $STH->fetchAll();

        return $events;
    }

    public
    function getSingleEvent($idEvent)
    {
        $STH = $this->connection->prepare("SELECT idEvents, name, date, details, entry, imgUrl, time, status, visible,
              note, performerEmail, performerPhoneNumber, youtubeVideo FROM Events WHERE idVenue = :idVenue AND idEvents = :idEvent;");
        $STH->bindParam(':idVenue', Validation::$idVenue);
        $STH->bindParam(':idEvent', $idEvent);
        $STH->execute();

        $event = $STH->fetchAll();

        return $event;
    }

    public
    function getTicketInfo($idEvent)
    {
        $STH = $this->connection->prepare("SELECT e.name as eventName, DATE_FORMAT(date,'%d.%m.%Y')as date,
            DATE_FORMAT(time,'%H:%m') as time, v.name as venueName, a.address_1 as street, a.city, a.zip
            FROM Events e
            INNER JOIN Venues v
            ON e.idVenue = v.idVenues
            INNER JOIN Address a
            ON a.idAddress = v.idAddress
            WHERE e.idEvents = :idEvent;");
        $STH->bindParam(':idEvent', $idEvent);
        $STH->execute();

        $event = $STH->fetch();

        return $event;
    }

    public
    function updateEvent($idEvent, $name, $date, $detail, $entry, $imgUrl, $time, $status, $visible, $notes, $performerEmail, $performerPhoneNumber, $youtubeVideo, $category)
    {
        $STH = $this->connection->prepare("UPDATE Events SET name = :name, date = :date, details = :detail, entry = :entry,
            imgUrl = :imgUrl, time = :time, status = :status, visible = :visible, note = :note, performerEmail = :performerEmail,
            performerPhoneNumber = :performerPhoneNumber, youtubeVideo = :youtubeVideo WHERE idEvents= :idEvent;");

        $STH->bindParam(':name', $name);
        $STH->bindParam(':date', $date);
        $STH->bindParam(':detail', $detail);
        $STH->bindParam(':entry', $entry);
        $STH->bindParam(':imgUrl', $imgUrl);
        $STH->bindParam(':time', $time);
        $STH->bindParam(':status', $status);
        $STH->bindParam(':visible', $visible);
        $STH->bindParam(':idEvent', $idEvent);
        $STH->bindParam(':note', $notes);
        $STH->bindParam(':performerEmail', $performerEmail);
        $STH->bindParam(':performerPhoneNumber', $performerPhoneNumber);
        $STH->bindParam(':youtubeVideo', $youtubeVideo);

        if ($STH->execute())
            return $this->updateCategory($idEvent, $category);
        return false;
    }

    public function updateEventImage($idEvent, $imgUrl)
    {
        $STH = $this->connection->prepare("UPDATE Events SET imgUrl = :imgUrl WHERE idEvents= :idEvent;");

        $STH->bindParam(':imgUrl', $imgUrl);
        $STH->bindParam(':idEvent', $idEvent);
        $STH->execute();

        $affectedRows = $STH->rowCount();

        return $affectedRows > 0;
    }

    /* private
     function updateEventBands($bands)
     {
         $affectedRows = 0;
         $STH = $this->connection->prepare("DELETE FROM Events_Bands WHERE idEvents =:idEvent; ");
         $STH->bindParam(':idEvent', $bands [0] ['idEvents']);
         $STH->execute();

         foreach ($bands as $band) {
             $STH = $this->connection->prepare("INSERT INTO Events_Bands
                     (idEvents, idBands, role, reward, extras, technicalNeeds, Notes)
                     VALUES (:idEvent, :idBand, :role, :reward, :extras, :technicalNeeds, :notes);");

             $STH->bindParam(':idEvent', $band ['idEvents']);
             $STH->bindParam(':idBand', $band ['idBands']);
             $STH->bindParam(':role', $band ['role']);
             $STH->bindParam(':reward', $band ['reward']);
             $STH->bindParam(':extras', $band ['extras']);
             $STH->bindParam(':technicalNeeds', $band ['technicalNeeds']);
             $STH->bindParam(':notes', $band ['Notes']);

             $STH->execute();

             $affectedRows += $STH->rowCount();
         }
         return $affectedRows == count($bands);
     }*/

    public
    function deleteEvent($idEvent)
    {
        $STH = $this->connection->prepare("DELETE FROM Events_Bands WHERE idEvents = :idEvent;");
        $STH->bindParam(':idEvent', $idEvent);
        $STH->execute();

        $STH = $this->connection->prepare("DELETE FROM Events WHERE idEvents =:idEvent;");
        $STH->bindParam(':idEvent', $idEvent);
        $STH->execute();
        $affectedRows = $STH->rowCount();

        return $affectedRows > 0;
    }

    /* -------------------------------Events------------------------------------------ */
    /* -------------------------------Category------------------------------------------ */

    private function assignCategory($eventId, $categoryId)
    {
        $STH = $this->connection->prepare("INSERT INTO EventCategory(idEvent, idCategory)VALUES(:idEvent, :idCategory)");
        $STH->bindParam(':idEvent', $eventId);
        $STH->bindParam(':idCategory', $categoryId);

        if ($STH->execute())
            return true;
        return false;
    }

    private function updateCategory($eventId, $categoryId)
    {
        $STH = $this->connection->prepare("UPDATE EventCategory SET idCategory = :idCategory WHERE idEvents= :idEvent;");

        $STH->bindParam(':idCategory', $categoryId);
        $STH->bindParam(':idEvent', $eventId);

        if ($STH->execute())
            return true;
        return false;
    }

    public function getCategory($eventId)
    {
        $STH = $this->connection->prepare("SELECT idEvent, e.idCategory, category FROM EventCategory e INNER JOIN CategoryList c
                        ON e.idCategory = c.idCategory
                        WHERE idEvent = :idEvent");
        $STH->bindParam(':idEvent', $eventId);
        $STH->execute();

        return $STH->fetch();
    }

    private function getCategoryList()
    {
        $STH = $this->connection->prepare("SELECT * FROM CategoryList");
        $STH->execute();

        return $STH->fetchAll();
    }
    /* -------------------------------Category------------------------------------------ */
    /* -------------------------------Bands------------------------------------------
    public
    function createBand($name, $email)
    {
        $STH = $this->connection->prepare("INSERT INTO Bands(name, email, idVenue)
				VALUES(:name, :email, :idVenue)");
        $STH->bindParam(':idVenue', Validation::$idVenue);
        $STH->bindParam(':name', $name);
        $STH->bindParam(':email', $email);

        if ($STH->execute()) {
            // band created
            $bandId = $this->connection->lastInsertId();

            // returns ID of band
            return $bandId;
        } else {
            // band failed to create
            return NULL;
        }
    }

    public
    function getAllBands()
    {
        $STH = $this->connection->prepare("SELECT idBands, name, email FROM Bands WHERE idVenue = :idVenue;");
        $STH->bindParam(':idVenue', Validation::$idVenue);
        $STH->execute();

        $bands = $STH->fetchAll();

        return $bands;
    }

    public
    function getSingleBand($idBand)
    {
        $STH = $this->connection->prepare("SELECT idBands, name, email FROM Bands WHERE idVenue = :idVenue AND idBands = :idBands;");
        $STH->bindParam(':idVenue', Validation::$idVenue);
        $STH->bindParam(':idBands', $idBand);
        $STH->execute();

        $band = $STH->fetchAll();

        return $band;
    }

    public
    function updateBand($idBand, $name, $email)
    {
        $STH = $this->connection->prepare("UPDATE Bands SET name = :name, email = :email WHERE idBands = :idBands;");

        $STH->bindParam(':name', $name);
        $STH->bindParam(':email', $email);
        $STH->bindParam(':idBands', $idBand);
        $STH->execute();

        $affectedRows = $STH->rowCount();

        return $affectedRows > 0;
    }

    public
    function deleteBand($idBand)
    {
        $STH = $this->connection->prepare("DELETE FROM Events_Bands WHERE idBands = :idBands;");
        $STH->bindParam(':idBands', $idBand);
        $STH->execute();

        $STH = $this->connection->prepare("DELETE FROM Bands WHERE idBands =:idBands;");
        $STH->bindParam(':idBands', $idBand);
        $STH->execute();
        $affectedRows = $STH->rowCount();

        return $affectedRows > 0;
    }

    -------------------------------Bands------------------------------------------ */
    /* ------------------------------Venues------------------------------------------ */
    public
    function getAllVenues()
    {
        $STH = $this->connection->prepare("SELECT idVenues, name, email, urlPhoto, state, city, zip, address_1, address_2, idAccount FROM Venues INNER JOIN Address
				ON Venues.idAddress = Address.idAddress;");
        $STH->execute();

        $venues = $STH->fetchAll();

        return $venues;
    }

    public
    function getSingleVenue($idVenue)
    {
        $STH = $this->connection->prepare("SELECT idVenues, name, email, urlPhoto, state, city, zip, address_1, address_2, idAccount FROM Venues INNER JOIN Address
				ON Venues.idAddress = Address.idAddress WHERE idVenues = :idVenue;");
        $STH->bindParam(':idVenue', $idVenue);
        $STH->execute();

        $venue = $STH->fetchAll();

        return $venue;
    }

    public function getVenuesNames($startsWith)
    {
        $startsWith = $startsWith . '%';
        $STH = $this->connection->prepare("SELECT idVenues, name FROM Venues WHERE LOWER(name) like LOWER(:startsWith);");
        $STH->bindParam(':startsWith', $startsWith);
        $STH->execute();

        $venues = $STH->fetchAll();

        return $venues;
    }

    public
    function updateVenue($urlPhoto)
    {
        $STH = $this->connection->prepare("UPDATE Venues SET urlPhoto = :urlPhoto WHERE idVenues = :idVenue;");

        $STH->bindParam(':urlPhoto', $urlPhoto);
        $STH->bindParam(':idVenue', Validation::$idVenue);

        $STH->execute();

        $affectedRows = $STH->rowCount();

        return $affectedRows > 0;
    }

    /* ------------------------------Venues------------------------------------------ */
    /* ----------------------------Favourites---------------------------------------- */
    public
    function createFavourite($idVenue2)
    {
        $STH = $this->connection->prepare("INSERT INTO Favorite(idVenue1, idVenue2)
				VALUES(:idVenue1, :idVenue2)");
        $STH->bindParam(':idVenue1', Validation::$idVenue);
        $STH->bindParam(':idVenue1', $idVenue2);

        if ($STH->execute()) {
            // favourite created
            $bandId = $this->connection->lastInsertId();

            // returns ID of favourite
            return $bandId;
        } else {
            // favourite failed to create
            return NULL;
        }
    }

    public
    function getAllFavourites()
    {
        $STH = $this->connection->prepare("SELECT idVenues, name, email, urlPhoto, state, city, zip, address_1, address_2, idAccount FROM Venues INNER JOIN Address
				ON Venues.idAddress = Address.idAddress INNER JOIN Favorite ON Favorite.idVenue1 = Venues.idVenues WHERE idVenues = :idVenue;");
        $STH->bindParam(':idVenue', Validation::$idVenue);
        $STH->execute();

        $favourites = $STH->fetchAll();

        return $favourites;
    }

    public
    function deleteFavourite($idFavourite)
    {
        $STH = $this->connection->prepare("DELETE FROM Favorite WHERE idFavorite = :idFavorite;");
        $STH->bindParam(':idFavorite', $idFavourite);
        $STH->execute();

        $affectedRows = $STH->rowCount();

        return $affectedRows > 0;
    }

    /* ----------------------------Favourites---------------------------------------- */
}