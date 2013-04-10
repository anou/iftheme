/**
 * INSTRUCTION FOR THEME TRANSLATION
 */

- use the default.pot file for translation in your language
- open with poedit for example
- translate all strings
- save your changes into a file (save as) named : LANG_CODE.po
(find the LANG_CODE here : http://codex.wordpress.org/WordPress_in_Your_Language)
- put it in the theme languages folder

if you don't have poedit, the rule to translate the file is :

for each "msgid" line, you must fill the "msgstr" line below. If you don't, the text will display in english by default.

msgid "Error 404 Not Found"
msgstr "YOUR TRANSLATION HERE"

...etc...

when you're finish save as LANG_CODE.po and put it in theme languages folder.