<?php
/* msqur - MegaSquirt .msq file viewer web application
Copyright 2014-2019 Nicholas Earwood nearwood@gmail.com https://nearwood.dev

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

require "msqur.php";

$msqur->header();

?>
<div>
<h2><img src=https://rusefi.com/forum/ext/rusefi/web/rusEFI_car.png>About rusEFI Online</h2>

<a href=https://github.com/rusefi/rusefi/wiki/Online>More on rusEFI wiki</a>

<br/>
<br/>


<a href=https://github.com/rusefi/msqur>https://github.com/rusefi/msqur</a>
based on
<a href=https://github.com/nearwood/msqur>https://github.com/nearwood/msqur</a>


	<h2><img src=https://raw.githubusercontent.com/nearwood/msqur/master/src/view/img/favicon.ico>About msqur</h2>
	<p>Created out of a need to share .MSQ files.</p>
	<p><a href=https://github.com/nearwood>Nick</a> was tired of downloading files and having to open them in Tuner Studio, so he created msqur.</p>
	<p>It's open source, so <a href="https://github.com/rusefi/web_backend">anyone can contribute to REO</a> or <a href="https://github.com/nearwood/msqur">msqur</a>.</p>
	<p><a href="https://github.com/rusefi/web_backend/issues">Issue Tracker</a></p>
</div>
<?php
$msqur->footer();
?>
