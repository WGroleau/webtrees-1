<?php
// Update the database schema from version 11 to 12
// - delete the wt_name.n_list column; it has never been used
// - a bug in webtrees 1.1.2 caused the wt_name.n_full column
// to include slashes around the surname.  These are unnecessary,
// and cause problems when we try to match the name from the
// gedcom with the name from the table.
//
// The script should assume that it can be interrupted at
// any point, and be able to continue by re-running the script.
// Fatal errors, however, should be allowed to throw exceptions,
// which will be caught by the framework.
// It shouldn't do anything that might take more than a few
// seconds, for systems with low timeout values.
//
// webtrees: Web based Family History software
// Copyright (C) 2011 Greg Roach
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

// Remove slashes from INDI names
self::exec("UPDATE `##name` SET n_full=REPLACE(n_full, '/', '') WHERE n_surn IS NOT NULL");

// Remove the n_list column
try {
	self::exec("ALTER TABLE `##name` DROP n_list");
} catch (PDOException $x) {
	// Already done?
}

// Update the version to indicate success
WT_Site::preference($schema_name, $next_version);

