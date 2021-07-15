<!DOCTYPE html>
<html>
    <head>

        <title>PHP Crawler</title>
        <style>
            body {
                font-family: arial, sans-serif;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            td, th {
                border: 1px solid #000;
                text-align: left;
                padding: 8px;
            }

            tr:nth-child(even) {
                background-color: #dddddd;
            }
        </style>
    </head>

    <body>

    <h1>This is a simple PHP Crawler</h1>
    <p>Code Challenge for the Backend Developer Position.</p>

    <?php
        include 'crawler.php';

        $startURL = 'https://agencyanalytics.com/features';
        $pages = 5;

        $crawler = new crawler($startURL, $pages);

        $crawler->run();

    ?>

    </body>
</html>
