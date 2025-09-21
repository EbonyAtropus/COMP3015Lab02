<?php

require_once 'Book.php';

class BookRepository
{
    private string $filename;

    /**
     * @param string $theFilename
     */
    public function __construct(string $theFilename)
    {
        $this->filename = $theFilename;
    }

    /**
     * @return array of Book objects
     */
    public function getAllBooks(): array
    {
        if (!file_exists($this->filename)) {
            return [];
        }

        $fileContents = file_get_contents($this->filename);
        if (!$fileContents) {
            return [];
        }

        $decodedBooks = json_decode($fileContents, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedBooks)) {
            return []; 
        }

        $books = [];
        foreach ($decodedBooks as $bookData) {
            if (!is_array($bookData) || empty($bookData)) {
                continue;
            }
            $books[] = (new Book())->fill($bookData);
        }
        return $books;
    }

    /**
     * Helper: read the file as an array of associative arrays (raw JSON objects)
     */
    private function readRawArray(): array
    {
        if (!file_exists($this->filename)) {
            return [];
        }
        $fileContents = file_get_contents($this->filename);
        if (!$fileContents) {
            return [];
        }

        $decoded = json_decode($fileContents, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    /**
     * Helper: write an array of associative arrays to the file (pretty print + lock)
     */
    private function writeRawArray(array $data): void
    {
        $data = array_values($data); 
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($this->filename, $json, LOCK_EX);
    }

    /**
     * @param Book $book
     */
    public function saveBook(Book $book): void
    {
        $raw = $this->readRawArray();
        $raw[] = $book->jsonSerialize();
        $this->writeRawArray($raw);
    }

    /**
     * Retrieves the book with the given ISBN, or null if not found.
     *
     * @param string $isbn
     * @return Book|null
     */
    public function getBookByISBN(string $isbn): Book|null
    {
        $books = $this->getAllBooks();
        foreach ($books as $book) {
            if ($book->getInternationalStandardBookNumber() === $isbn) {
                return $book;
            }
        }
        return null;
    }

    /**
     * Retrieves the book with the given title, or null if not found.
     *
     * @param string $title
     * @return Book|null
     */
    public function getBookByTitle(string $title): Book|null
    {
        $books = $this->getAllBooks();
        foreach ($books as $book) {
            if ($book->getName() === $title) {
                return $book;
            }
        }
        return null;
    }

    /**
     * Updates the book in the file with the given $isbn (replace first match with $newBook)
     * @param string $isbn
     * @param Book $newBook
     */
    public function updateBook(string $isbn, Book $newBook): void
    {
        $raw = $this->readRawArray();
        $updated = false;
        foreach ($raw as $idx => $bookData) {
            if (isset($bookData['isbn']) && $bookData['isbn'] === $isbn) {
                $raw[$idx] = $newBook->jsonSerialize();
                $updated = true;
                break;
            }
        }
        if ($updated) {
            $this->writeRawArray($raw);
        }
    }

    /**
     * Deletes the book in the file with the given $isbn.
     * @param string $isbn
     */
    public function deleteBookByISBN(string $isbn): void
    {
        $raw = $this->readRawArray();
        $changed = false;
        foreach ($raw as $idx => $bookData) {
            if (isset($bookData['isbn']) && $bookData['isbn'] === $isbn) {
                unset($raw[$idx]);
                $changed = true;
            }
        }
        if ($changed) {
            $this->writeRawArray(array_values($raw));
        }
    }

}
