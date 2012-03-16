{*
 * Copyright (c) 2004-2012 OIC Group, Inc.
 *
 * This file is part of Exponent
 *
 * Exponent is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * GPL: http://www.gnu.org/licenses/gpl.txt
 *
 *}

{css unique="custom-report-buttons" corecss="button"}
    {$css}
{/css}

{eval var=$template}

{if $is_email == 0}
	<a class="awesome {$smarty.const.BTN_SIZE} {$smarty.const.BTN_COLOR}" href="{$backlink}">{'Back'|gettext}</a>
{/if}