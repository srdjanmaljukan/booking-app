<?php
// Tajni ključ kojim potpisujemo i provjeravamo JWT tokene.
// Niko van servera ne smije znati ovu vrijednost — ko god je zna, može
// da napravi lažne, validne tokene.
define('JWT_SECRET', 'zamijeni-ovo-nekim-dugackim-nasumicnim-stringom-123!@#');