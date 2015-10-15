/**
 * INSTRUCTION FOR THEME TRANSLATION
 */

- use the xx_XX.pot file for translation in your language
- open with poedit for example
- translate all strings
- save your changes into a file (save as) named : lang_CODE.po (ex.: fr_FR.po)
(find the LANG_CODE here : http://codex.wordpress.org/WordPress_in_Your_Language)
- put it in the theme languages folder

if you don't have poedit, the rule to translate the file is :

for each "msgid" line, you must fill the "msgstr" line below. If you don't, the text will display in english by default.

msgid "Error 404 Not Found"
msgstr "YOUR TRANSLATION HERE"

...etc...

when you're finish save as lang_CODE.po and put it in theme languages folder.

Thanks to you for sending me the final .po file so I can integrate it in the IF Theme package.