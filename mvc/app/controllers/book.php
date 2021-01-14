<?php

class Book extends Controller {
      public function register() {        
        session_start();
        if (!isset($_SESSION['rights']) || 
           ($_SESSION['rights']!='admin' && $_SESSION['rights']!='konyvtaros'))        
            return;
        else
            $rights = $_SESSION['rights'];
        
        $error = '';
        if (array_key_exists('first', $_POST))
        {          
            $bookModel = $this->model('BookModel');
            if ($bookModel->isIsbnExists($_POST['ISBN']))
            {
                if (!empty($_POST['cim']))
                    $error = $bookModel->isIsbnMistyped($_POST['ISBN'], $_POST['szerzok'], $_POST['cim']);
                if (!$error)                                      
                    $error = $bookModel->registerCopy($_POST['ISBN'], $_POST['azonosito']);                
            }
            else 
            {       
                $isbn = trim($_POST['ISBN']);
                $szerzok = trim($_POST['szerzok']);
                $cim = trim($_POST['cim']);
                $kiado = trim($_POST['kiado']);
                $kiadasi_ev = $_POST['kiadasi_ev'];
                $cutter = trim($_POST['cutter']);
                $eto_jelzet = trim($_POST['ETO_jelzet']);
                $oldalak_szama = $_POST['oldalak_szama'];
                $targyszavak = trim($_POST['targyszavak']);
                $azonosito = $_POST['azonosito'];               
                
                $error = $bookModel->validateBookData($isbn, $szerzok, $cim, $kiado, $kiadasi_ev, 
                                                        $cutter, $eto_jelzet, $oldalak_szama, 
                                                        $targyszavak, $azonosito);
                if (!$error)
                    $error = $bookModel->registerNewBook($isbn, $szerzok, $cim, $kiado, $kiadasi_ev, 
                                                        $cutter, $eto_jelzet, $oldalak_szama, 
                                                        $targyszavak, $azonosito);                                
            }                        
            if (!$error)
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/felvetel_sikeres', $_POST['ISBN']);                
            }
            else 
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$error]);
            }
        }
        else if (array_key_exists('second', $_POST))
        {
            $isbn = $_POST['ISBN'];            
            $bookModel = $this->model('BookModel');
            $result = $bookModel->getBookDatabyIsbn($isbn);
            if (isset($result['data'])) {
                $bookdata = $result['data'];            
                $this->view('header/header_urlap_2');
                $this->viewNavigation($rights);
                $this->view('book/felvetel', [$bookdata]);
            }
            else {             
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$result['error']]);            
            }
        } 
        else
        {
            $this->view('header/header_urlap_2');
            $this->viewNavigation($rights);
            $this->view('book/felvetel');     
        }                
    }  
    
    public function delete() {
        session_start();
        if (!isset($_SESSION['rights']) || 
           ($_SESSION['rights']!='admin' && $_SESSION['rights']!='konyvtaros'))        
            return;
        else
            $rights = $_SESSION['rights'];

        
        if (array_key_exists('delete', $_POST))
        {
            $bookModel = $this->model('BookModel');
            $error = $bookModel->deleteBookById($_POST['azonosito']);
            if (!$error)
                $message = "A könyvpéldányt sikeresen törölte a katalógusból.";
            else 
                $message = $error;
            $this->view('header/header_urlap_1');
            $this->viewNavigation($rights);
            $this->view('book/uzenet', [$message]);            
        }
        else if (array_key_exists('getdata', $_POST))
        {
            $bookModel = $this->model('BookModel');
            $result = $bookModel->getBookDataById($_POST['azonosito']);
            if (isset($result['data']))
            {
                $this->view('header/header_urlap_3');
                $this->viewNavigation($rights);
                $this->view('book/torles', [$result['data']]);            
            }
            else
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$result['error']]);                
            }
        }
        else 
        {
            $this->view('header/header_urlap_3');
            $this->viewNavigation($rights);
            $this->view('book/torles');            
        }
    }
    
        public function choose($param) 
    {                
        session_start();
        if (!isset($_SESSION['rights']) || 
           ($_SESSION['rights']!='admin' && $_SESSION['rights']!='konyvtaros'))        
            return;
        else
            $rights = $_SESSION['rights'];

        
        if (array_key_exists('findbyid', $_POST))
        {            
            $id = $_POST['azonosito'];
            $readerModel = $this->model('ReaderModel');
            $response = $readerModel->findReaderById($id);
            if (isset($response['error']))
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$response['error']]);                
            }
            else 
            {                   
                switch ($param)
                {
                    case 'toborrow':
                        $this->toBorrow($response['data']->Olvasojegy_azonosito, 0);
                        break;
                    case 'toreturn':
                        $this->toReturn($response['data']->Olvasojegy_azonosito, 0, 0);
                        break;
                    case 'toprolong':
                        $this->toProlong($response['data']->Olvasojegy_azonosito);
                        break;
                }
            }
        }      
        else if (array_key_exists('findbyname', $_POST))
        {
            $name = $_POST['olvasonev'];
            $readerModel = $this->model('ReaderModel');            
            $response = $readerModel->findReadersByName($name);            
            if (isset($response['error']))
            {
                $error = $response['error'];
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$error]);                
            }
            else 
            {              
                $readers = $response['data'];                
                $this->view('header/header_urlap_3_5');
                $this->viewNavigation($rights);
                $this->view('book/kolcsonzo_lista', ['operation' => $param, 'readers' => $readers]);
            }            
        }
        else if (array_key_exists('choosebyname', $_POST))
        {
            $id = $_POST['azonosito'];
            $readerModel = $this->model('ReaderModel');
            $response = $readerModel->findReaderById($id);                               
            switch ($param)
            {
                case 'toborrow':
                    $this->toBorrow($response['data']->Olvasojegy_azonosito, 0);
                    break;
                case 'toreturn':
                    $this->toReturn($response['data']->Olvasojegy_azonosito, 0, 0);
                    break;
                case 'toprolong':
                    $this->toProlong($response['data']->Olvasojegy_azonosito);
                    break;
            }            
        }
        else            
        {          
            $this->view('header/header_urlap_3');
            $this->viewNavigation($rights);
            $this->view('book/kolcsonzo_azonositas', ['operation' => $param]);
        }                
    }
      
    public function toBorrow($olvaso, $peldany) {
        if (session_status() != PHP_SESSION_ACTIVE)
            session_start();        
        if (!isset($_SESSION['rights']) || 
           ($_SESSION['rights']!='admin' && $_SESSION['rights']!='konyvtaros'))        
            return;
        else
            $rights = $_SESSION['rights'];
        
        $readerModel = $this->model('ReaderModel');
        $readerData = $readerModel->findReaderById($olvaso);
        
        if (array_key_exists('adatlekeres', $_POST))
        {
            if ((empty($peldany) || $peldany == 0) && empty($_POST['azonosito']))
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["Kérem adjon meg egy könyvpéldány azonosítót! <br><br>"
                    ."<a href=".BASEURL."/book/toborrow/$olvaso/0'>Vissza</a>"]);
                return;                
            }            
            $bookModel = $this->model('BookModel');
            if ($peldany == 0)
                $bookData = $bookModel->getBookDataById($_POST['azonosito']);
            else
                $bookData = $bookModel->getBookDataById($peldany);
            if (isset($bookData['data']))
            {            
                $this->view('header/header_urlap_3');
                $this->viewNavigation($rights);
                $this->view('book/kikolcsonzes', ['reader' => $readerData['data'], 
                                                   'book' => $bookData['data']]);
            }
            else {                
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$bookData['error'] . "<br><br>"
                    ."<a href=".BASEURL."/book/toborrow/$olvaso/0'>Vissza</a>"]);
            }
        }        
        else if (array_key_exists('kikolcsonzes', $_POST))
        {     
            if ((empty($peldany) || $peldany == 0) && empty($_POST['azonosito']))
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["Kérem adjon meg egy könyvpéldány azonosítót!<br><br>"
                    ."<a href=".BASEURL."/book/toborrow/$olvaso/0'>Vissza</a>"]);
                return;                
            }        
            
            $bookModel = $this->model('BookModel');
            if ($peldany == 0)
                $bookData = $bookModel->getBookDataById($_POST['azonosito']);
            else
                $bookData = $bookModel->getBookDataById($peldany);
            
            if (isset($bookData['error'])) {                   
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$bookData['error']. "<br><br>"
                    ."<a href=".BASEURL."/book/toborrow/$olvaso/0'>Vissza</a>"]); 
                return;
            }
            
            if (!$bookModel->isBookBorrowed($bookData['data']->Azonosito))
            {         
                $bookModel->borrowBook($olvaso, $bookData['data']->Azonosito, $_SESSION['username']);                       
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["Sikeres kikölcsönzés.<br><br>"
                    . "<a href=".BASEURL."/book/toborrow/$olvaso/0'>Újabb könyv kikölcsönzése</a>"]);
                return;                
            }
            else {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["A könyv már ki van kölcsönözve, nem adható ki! <br><br>"
                    ."<a href=".BASEURL."/book/toborrow/$olvaso/0'>Vissza</a>"]);
            }
        }
        else {
            $this->view('header/header_urlap_3');
            $this->viewNavigation($rights);
            $this->view('book/kikolcsonzes', ['reader' => $readerData['data']]);        
        }
    }
    
    public function toReturn($olvaso, $peldany, $osszkeses) {
        if (session_status() != PHP_SESSION_ACTIVE)
            session_start();        
        if (!isset($_SESSION['rights']) || 
           ($_SESSION['rights']!='admin' && $_SESSION['rights']!='konyvtaros'))        
            return;
        else
            $rights = $_SESSION['rights'];

        
        $readerModel = $this->model('ReaderModel');
        $readerData = $readerModel->findReaderById($olvaso);
        
        if (array_key_exists('adatlekeres', $_POST))
        {
            if ((empty($peldany) || $peldany == 0) && empty($_POST['azonosito']))
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["Kérem adjon meg egy könyvpéldány azonosítót!<br><br>"
                    . "<a href=".BASEURL."/book/toreturn/$olvaso/0/$osszkeses'>Vissza</a>"]);
                return;                
            }            
            $bookModel = $this->model('BookModel');
            if ($peldany == 0)
                $bookData = $bookModel->getBookDataById($_POST['azonosito']);
            else
                $bookData = $bookModel->getBookDataById($peldany);
            if (isset($bookData['data']))
            {            
                $this->view('header/header_urlap_3');
                $this->viewNavigation($rights);
                $this->view('book/visszavetelezes', ['reader' => $readerData['data'], 
                                                     'book' => $bookData['data'], 
                                                     'delay' => $osszkeses]);
            }
            else {                
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$bookData['error']
                    ."<br><br><a href=".BASEURL."/book/toreturn/$olvaso/0/$osszkeses'>Vissza</a>"]);
                return;
            }
        }
        else if (array_key_exists('visszavetel', $_POST))
        {     
            if ((empty($peldany) || $peldany == 0) && empty($_POST['azonosito']))
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["Kérem adjon meg egy könyvpéldány azonosítót!<br><br>"
                    . "<a href=".BASEURL."/book/toreturn/$olvaso/0/$osszkeses'>Vissza</a>"]);
                return;                
            }        
            
            $bookModel = $this->model('BookModel');
            if ($peldany == 0)
                $bookData = $bookModel->getBookDataById($_POST['azonosito']);
            else
                $bookData = $bookModel->getBookDataById($peldany);
            
            if (isset($bookData['error'])) {                   
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', [$bookData['error']
                    ."<br><br><a href=".BASEURL."/book/toreturn/$olvaso/0/$osszkeses'>Vissza</a>"]);
                return;
            }
            
            if ($bookModel->isBookBorrowed($bookData['data']->Azonosito))
            {       
                $result = $bookModel->delayInDays($olvaso, $bookData['data']->Azonosito);
                if ($bookModel->returnBook($olvaso, $bookData['data']->Azonosito, $_SESSION['username']))                       
                {                                          
                    if ($result && $result->Days > 0)
                        $delay = $result->Days;
                    else 
                        $delay = 0;
                    unset($_POST['visszavetel']);
                    $this->toReturn($olvaso, 0, $osszkeses + $delay);
                }
                else {
                    $this->view('header/header_urlap_1');
                    $this->viewNavigation($rights);
                    $this->view('book/uzenet', ["A könyv visszavételezése nem sikerült, mert "
                        . "nem a megadott olvasó kölcsönözte ki!" 
                        ."<br><br><a href=".BASEURL."/book/toreturn/$olvaso/0/$osszkeses'>Vissza</a>"]);
                    return;
                }
            }
            else {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["A könyv már vissza van vételezve!"
                    ."<br><br><a href=".BASEURL."/book/toreturn/$olvaso/0/$osszkeses'>Vissza</a>"]);
                return;
            }
        }
        else if (array_key_exists('befejezes', $_POST))
        {
            $kesedelmi_dij = $osszkeses * 5;
            $this->view('header/header_urlap_1');
            $this->viewNavigation($rights);
            if ($kesedelmi_dij > 0)
                $this->view('book/uzenet', ["Összesen $kesedelmi_dij Ft késedelmi díj fizetendő."]);
            else
                $this->view('book/uzenet', ["Nem fizetendő késedelmi díj."]);
        }
        else {
            $this->view('header/header_urlap_3');
            $this->viewNavigation($rights);
            $this->view('book/visszavetelezes', ['reader' => $readerData['data'], 'delay' => $osszkeses]);        
        }
    }
    
    public function toProlong($olvaso) {
        if (session_status() != PHP_SESSION_ACTIVE)
            session_start();        
        if (!isset($_SESSION['rights']) || 
           ($_SESSION['rights']!='admin' && $_SESSION['rights']!='konyvtaros'))        
            return;
        else
            $rights = $_SESSION['rights'];

        
        $readerModel = $this->model('ReaderModel');
        $readerData = $readerModel->findReaderById($olvaso);
        
        if (array_key_exists('hosszabbitas', $_POST))
        {
            $bookModel = $this->model('BookModel');
            $books = $bookModel->getBorrowedBooksbyReader($olvaso);
            $books_not_prolonged = [];
            foreach ($books as $book)
            {
                if (array_key_exists($book->Azonosito, $_POST))
                {
                    $result = $bookModel->isBookProlongable($olvaso, $book->Azonosito);                    
                    if ($result->Hosszabbitva > 0 || $result->MaxHatarido <= date('Y-m-d'))
                    {
                        $books_not_prolonged[] = $book;
                    }
                    else {                    
                        $bookModel->prolongBorrow($olvaso, $book->Azonosito, $_SESSION['username']);
                    }
                }                                        
            }
            if (count($books_not_prolonged) > 0)
            {
                $this->view('header/header_urlap_3_6');
                $this->viewNavigation($rights);
                $this->view('book/hosszabbitas_nem', ['reader' => $readerData['data'], 'books' => $books_not_prolonged]);            
            } 
            else {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["A kijelölt könyv(ek) kölcsönzési határideje meghosszabbításra került."]);
            }
        }
        else 
        {
            $bookModel = $this->model('BookModel');
            $books = $bookModel->getBorrowedBooksbyReader($olvaso); 
            if ($books != null)
            {
                $this->view('header/header_urlap_3_6');
                $this->viewNavigation($rights);
                $this->view('book/hosszabbitas', ['reader' => $readerData['data'], 'books' => $books]);            
            }
            else
            {
                $this->view('header/header_urlap_1');
                $this->viewNavigation($rights);
                $this->view('book/uzenet', ["Az olvasónak jelenleg nincsenek kikölcsönzött könyvei."]);
            }
        }
        
    }

    
}

