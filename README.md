<pre>
Today .................... [||||||||||] 5
..........................
Yesterday ................ [||........] 1
2 days ago ............... [||||......] 2
3 days ago ............... [||........] 1
4 days ago ............... [..........] 
5 days ago ............... [||||||....] 3
6 days ago ............... [||||......] 2
7 days ago ............... [||........] 1
In the last 8 days ....... ............ 15
..........................
Total .................... ............ 4213
Counted since ............  //
Visitors per day ......... ............ 0.8
</pre>

Example output. In this example "$nur_im_source_anzeigen" (=just display in source) "$besuche_zusammenfassen" (summarize page impressions by one single user) are set to "false".

# Install

Simply include into your HTML-code and start it e.g. like this:

```php
<?php
$spc = new spc();
$spc->sprache = 'en';
$spc->email = 'meine@email-adresse.de'; // for statistcs
$spc->email_nach_tagen = 14; // send stats every X days
$spc->besuche_zusammenfassen = false; // summarize page impressions by one single user
$spc->nur_im_source_anzeigen = false; // display only in source
$spc->dateipfad_zur_txt = 'foldername/';
$spc->start();
?>
```

The script generates automatically a textfile with the name "spc_NAME-OF-YOUR-FILE.txt" and saves the number of visitors per day during the last seven days and the total number of visitors into it.
The numbers are represented by an ASCII diagram.

I designed this script as a simple php counter which is only displayed in the source code.
It is however also configurable in a way that it is visible on the Website.

When a session is set, the page impressions of a single user can be summarized (default).
The script runs a simple check whether the visitor is a bot. If yes he can be ignored (default).
The path to the textfile can be specified. Default: “spc/”. If the folder or file doesn't exist it will be generated.
