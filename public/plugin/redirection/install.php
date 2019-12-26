<?php

/* For licensing terms, see /license.txt */
/**
 * Install.
 *
 * @author Enrique Alcaraz Lopez
 */
api_protect_admin_script();

RedirectionPlugin::create()->install();
