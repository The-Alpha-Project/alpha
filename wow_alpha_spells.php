<?php

/*
  Author: X'Genesis Qhulut <XGenesis-Qhulut@protonmail.com>
  Date:   August 2022

  See LICENSE for license details.
*/

// SPELLS

function simulateSpell ($id, $row)
  {
  global $spells, $items;
  global $documentRoot, $executionDir;

  echo "<p><div class='spell'>\n";
  echo "<h3 style='color:yellow;'>" . htmlspecialchars ($row ['Name_enUS']) . "</h3>\n";

  // spell icon

  // fallback icon: INV_Misc_QuestionMark.png

  $imageRow = dbQueryOneParam ("SELECT * FROM ".SPELLICON." WHERE ID = ?", array ('i', &$row ['SpellIconID']));

  if ($imageRow)
    {
    $TextureFilename = $imageRow ['TextureFilename'] ;

    if (preg_match ("|([^\\\\]+)$|i", $TextureFilename, $matches))
      $TextureFilename = $matches [1];
    $TextureFilename  .= '.png';

    if (file_exists ("$documentRoot$executionDir/icons/$TextureFilename"))
      echo "<img src='icons/$TextureFilename' alt='Spell icon' title='" . htmlspecialchars ($imageRow ['TextureFilename']) . "'>\n";
    else
      echo "<img src='icons/INV_Misc_QuestionMark.png' alt='Item icon' title='INV_Misc_QuestionMark'>\n";
    }
  else
    echo "<img src='icons/INV_Misc_QuestionMark.png' alt='Item icon' title='INV_Misc_QuestionMark'>\n";

  // spell type (left) and mana cost (right)
  echo "<div>\n";
  echo "<p class='item_lh'>" . expandSimple (SPELL_SCHOOLS,   $row ['School'], false)  . " Magic </p>\n";
  if ($row ['ManaCost'] )
    echo "<p class='item_rh'>" . $row ['ManaCost'] . ' ' . expandSimple (POWER_TYPES, $row ['PowerType'], false) . " </p>\n";
  echo "</div>\n";
  // clear float
  echo "<div style='clear: both;'></div>\n";

  // look up the cast time in another table
  $spellCastTimeRow = dbQueryOneParam ("SELECT * FROM ".SPELLCASTTIMES." WHERE ID = ?", array ('i', &$row ['CastingTimeIndex']));

  // cast time (left) and cooldown time (right)
  echo "<div>\n";
  echo "<span class='item_lh'>";
  if ($spellCastTimeRow ['Base'] == 0)
    echo "Instant cast\n";
  else
    echo convertTimeSeconds ($spellCastTimeRow ['Base']) . " sec cast\n";
  echo "</span><span class='item_rh'>";
  if ($row ['CategoryRecoveryTime'])
    if ($row ['CategoryRecoveryTime'] >= 60000)
      echo convertTimeMinutes($row ['CategoryRecoveryTime']) . " min cooldown\n";
    else
      echo convertTimeSeconds($row ['CategoryRecoveryTime']) . " sec cooldown\n";
  echo "</span></div>\n";
  // clear float
  echo "<div style='clear: both;'></div>\n";

  // look up the range in yet another table
  $spellRangeRow = dbQueryOneParam ("SELECT * FROM ".SPELLRANGE." WHERE ID = ?", array ('i', &$row ['RangeIndex']));

  if ($spellRangeRow ['RangeMax'] > 0)
    echo '<br>' . $spellRangeRow ['RangeMax'] . ' yd range';

  // look up the duration in yet another table again
  $spellDurationRow = dbQueryOneParam ("SELECT * FROM ".SPELLDURATION." WHERE ID = ?", array ('i', &$row ['DurationIndex']));

  // show what it casts

  if (getCount ($row, 'EffectTriggerSpell_', 3))
    {
    echo "<p><b>Effect trigger spells:</b><br>\n";
    for ($i = 1; $i <= 3; $i++)
      if ($row ["EffectTriggerSpell_$i"])
         echo '<br>' . lookupThing ($spells, $row ["EffectTriggerSpell_$i"], 'show_spell');
    }

  // show effects

  if (getCount ($row, 'Effect_', 3))
    {
    echo "<p><b>Effects:</b>\n";
    for ($i = 1; $i <= 3; $i++)
      if ($row ["Effect_$i"])
         echo '<br>' . expandSimple (SPELL_EFFECTS, $row ["Effect_$i"], false);
    }

  // show effect auras
  if (getCount ($row, 'EffectAura_', 3))
    {
    echo "<p><b>Auras:</b>\n";
    for ($i = 1; $i <= 3; $i++)
      if ($row ["EffectAura_$i"])
         echo '<br>' . expandSimple (SPELL_AURAS, $row ["EffectAura_$i"]);
    } // end if any auras


  // reagents

  if (getCount ($row, 'Reagent_', 8))
    {
    echo "<p><b>Reagents:</b><br>\n";
    echo (lookupItems ($row,
                 array ('Reagent_1', 'Reagent_2', 'Reagent_3', 'Reagent_4', 'Reagent_5', 'Reagent_6', 'Reagent_7', 'Reagent_8'),
                 array ('ReagentCount_1', 'ReagentCount_2', 'ReagentCount_3', 'ReagentCount_4', 'ReagentCount_5', 'ReagentCount_6', 'ReagentCount_7', 'ReagentCount_8')));
    }

  // show effect items

  if (getCount ($row, 'EffectItemType_', 3))
    {
    echo "<p><b>Effect items:</b><br>\n";
    tdh (lookupItems ($row,
                 array ('EffectItemType_1',   'EffectItemType_2',   'EffectItemType_3'),
                 array ('EffectMiscValue_1',  'EffectMiscValue_2',  'EffectMiscValue_3')));
    }


  echo "<hr>\n";

  $s1 =

  $description = $row ['Description_enUS'];

  // calculate spell roll ranges for all three effect die
  for ($i = 1; $i <= 3; $i++)
    $description = str_replace ('$s' . $i,
                  spellRoll ($row ["EffectDieSides_$i"], $row ["EffectBaseDice_$i"], $row ["EffectDicePerLevel_$i"],
                             $row ["EffectBasePoints_$i"]),
                    $description);

  $description = str_replace ('$d',  convertTimeGeneral ($spellDurationRow ['Duration']), $description);

  echo "<span style='color:yellow;'>" . htmlspecialchars ($description) . "</span>\n";



  echo "</div>\n";    // end of simulation box

  } // end of

function showOneSpell ($id)
  {
  showOneThing (SPELL, 'alpha_dbc.spell', 'ID', $id, "Spell", "Name_enUS",
                array (
                  'Reagent_1' => 'item',
                  'Reagent_2' => 'item',
                  'Reagent_3' => 'item',
                  'Reagent_4' => 'item',
                  'Reagent_5' => 'item',
                  'Reagent_6' => 'item',
                  'Reagent_7' => 'item',
                  'Reagent_8' => 'item',
                  'EffectItemType_1' => 'item',
                  'EffectItemType_2' => 'item',
                  'EffectItemType_3' => 'item',
                  'PowerType' => 'power_type',
                  'School' => 'spell_school',
                  'EffectTriggerSpell_1' => 'spell',
                  'EffectTriggerSpell_2' => 'spell',
                  'EffectTriggerSpell_3' => 'spell',
                  'Effect_1' => 'spell_effect',
                  'Effect_2' => 'spell_effect',
                  'Effect_3' => 'spell_effect',
                  'Targets' => 'spell_target_type_mask',
                  'Attributes' => 'spell_attributes_mask',
                  'AttributesEx' => 'spell_attributes_ex_mask',
                  'EquippedItemClass' => 'item_class',
                  'EquippedItemSubclass' => 'item_subclass_mask',
                  'EffectAura_1' => 'spell_aura',
                  'EffectAura_2' => 'spell_aura',
                  'EffectAura_3' => 'spell_aura',

                ), 'simulateSpell');
  } // end of showOneSpell

function showSpells ()
  {
  global $where, $params, $sort_order;

  $sortFields = array (
    'ID',
    'Name_enUS',
    'NameSubtext_enUS',
    'School',
    'Category',
    'PowerType',
    'Description_enUS',
  );

  if (!in_array ($sort_order, $sortFields))
    $sort_order = 'Name_enUS';



  echo "<h2>Spells</h2>\n";

  $td  = function ($s) use (&$row) { tdx ($row  [$s]); };
  $tdr = function ($s) use (&$row) { tdx ($row  [$s], 'tdr'); };

  setUpSearch ('ID', array ('Name_enUS', 'Description_enUS'));

  $offset = getQueryOffset(); // based on the requested page number

  $results = dbQueryParam ("SELECT * FROM ".SPELL." $where ORDER BY $sort_order, ID LIMIT $offset, " . QUERY_LIMIT,
            $params);

  if (!showSearchForm ($sortFields, $results, SPELL, $where))
    return;

  echo "<table class='search_results'>\n";
  headings (array ('ID', 'Name', 'Subtext', 'School', 'Category',
                   'Power Type', 'Reagents', 'Effect Item', 'Description'));
  foreach ($results as $row)
    {
    echo "<tr>\n";
    $id = $row ['ID'];
    tdhr ("<a href='?action=show_spell&id=$id'>$id</a>");
    $td ('Name_enUS');
    $td ('NameSubtext_enUS');
    $school = $row ['School'];
    tdx ("$school: " . SPELL_SCHOOLS [$school]);
    $tdr ('Category');
    $powerType = $row ['PowerType'];
    tdx ("$powerType: " . POWER_TYPES [$powerType]);
    tdh (lookupItems ($row,
                 array ('Reagent_1', 'Reagent_2', 'Reagent_3', 'Reagent_4', 'Reagent_5', 'Reagent_6', 'Reagent_7', 'Reagent_8'),
                 array ('ReagentCount_1', 'ReagentCount_2', 'ReagentCount_3', 'ReagentCount_4', 'ReagentCount_5', 'ReagentCount_6', 'ReagentCount_7', 'ReagentCount_8')));
    tdh (lookupItems ($row,
                 array ('EffectItemType_1',   'EffectItemType_2',   'EffectItemType_3'),
                 array ('EffectMiscValue_1',  'EffectMiscValue_2',  'EffectMiscValue_3')));
    $td ('Description_enUS');
    echo "</tr>\n";
    }
  echo "</table>\n";

  showCount ($results);

  } // end of showSpells

?>
