# MD5

The script is designed to extract MD5 hash files from the database and check them for viruses.

### script-yesterday.php works like this:

1. Selects hashe from yesterday.
2. Sends it to https://hash.cymru.com/ to be checked for viruses.
3. Returns the checked file.
4. Sifts through hashes. (Leaves only the infected ones).
5. Searches all information in the database for infected hashes.
6. Throws it into a file.
7. Sends to email.

### script-all.php performs the same functions, but selects the MD5 hash for the entire period.

!! script-all.php needs to be run 1 time, at the beginning of work, after installing the script.
