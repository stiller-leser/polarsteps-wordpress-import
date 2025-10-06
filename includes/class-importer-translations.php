<?php
class Polarsteps_Importer_Translations {

    /**
     * Translates a country name to the target language based on the site's locale.
     *
     * @param string $country_name The country name from Polarsteps (usually in English).
     * @return string The translated country name or the original name if no translation is found.
     */
    public static function translate_country($country_name) {
        $target_locale = get_locale();
        $lang_code = substr($target_locale, 0, 2); // e.g., 'de_DE' -> 'de'

        // The list is keyed by the English name.
        $countries = self::get_countries();

        if (isset($countries[$country_name]) && isset($countries[$country_name][$lang_code])) {
            // Direct match found (e.g., input is "Germany", target is 'de')
            return $countries[$country_name][$lang_code];
        }

        // If no direct match, maybe the input is already translated? Search all translations.
        foreach ($countries as $english_name => $translations) {
            if (in_array($country_name, $translations, true)) {
                // Found the country, now return the version for the target language.
                if (isset($translations[$lang_code])) {
                    return $translations[$lang_code];
                }
                // If target language is not available, return the English name as a fallback.
                return $english_name;
            }
        }

        // If no translation is found anywhere, return the original name.
        return $country_name;
    }

    /**
     * Returns a list of countries with their translations.
     * Keys are English names.
     *
     * @return array
     */
    private static function get_countries() {
        return [ // English names are the keys
            'Afghanistan' => ['en' => 'Afghanistan', 'de' => 'Afghanistan', 'es' => 'Afganistán', 'fr' => 'Afghanistan', 'it' => 'Afghanistan'],
            'Albania' => ['en' => 'Albania', 'de' => 'Albanien', 'es' => 'Albania', 'fr' => 'Albanie', 'it' => 'Albania'],
            'Algeria' => ['en' => 'Algeria', 'de' => 'Algerien', 'es' => 'Argelia', 'fr' => 'Algérie', 'it' => 'Algeria'],
            'Andorra' => ['en' => 'Andorra', 'de' => 'Andorra', 'es' => 'Andorra', 'fr' => 'Andorre', 'it' => 'Andorra'],
            'Angola' => ['en' => 'Angola', 'de' => 'Angola', 'es' => 'Angola', 'fr' => 'Angola', 'it' => 'Angola'],
            'Antigua and Barbuda' => ['en' => 'Antigua and Barbuda', 'de' => 'Antigua und Barbuda', 'es' => 'Antigua y Barbuda', 'fr' => 'Antigua-et-Barbuda', 'it' => 'Antigua e Barbuda'],
            'Argentina' => ['en' => 'Argentina', 'de' => 'Argentinien', 'es' => 'Argentina', 'fr' => 'Argentine', 'it' => 'Argentina'],
            'Armenia' => ['en' => 'Armenia', 'de' => 'Armenien', 'es' => 'Armenia', 'fr' => 'Arménie', 'it' => 'Armenia'],
            'Australia' => ['en' => 'Australia', 'de' => 'Australien', 'es' => 'Australia', 'fr' => 'Australie', 'it' => 'Australia'],
            'Austria' => ['en' => 'Austria', 'de' => 'Österreich', 'es' => 'Austria', 'fr' => 'Autriche', 'it' => 'Austria'],
            'Azerbaijan' => ['en' => 'Azerbaijan', 'de' => 'Aserbaidschan', 'es' => 'Azerbaiyán', 'fr' => 'Azerbaïdjan', 'it' => 'Azerbaigian'],
            'Bahamas' => ['en' => 'Bahamas', 'de' => 'Bahamas', 'es' => 'Bahamas', 'fr' => 'Bahamas', 'it' => 'Bahamas'],
            'Bahrain' => ['en' => 'Bahrain', 'de' => 'Bahrain', 'es' => 'Baréin', 'fr' => 'Bahreïn', 'it' => 'Bahrein'],
            'Bangladesh' => ['en' => 'Bangladesh', 'de' => 'Bangladesch', 'es' => 'Bangladés', 'fr' => 'Bangladesh', 'it' => 'Bangladesh'],
            'Barbados' => ['en' => 'Barbados', 'de' => 'Barbados', 'es' => 'Barbados', 'fr' => 'Barbade', 'it' => 'Barbados'],
            'Belarus' => ['en' => 'Belarus', 'de' => 'Belarus', 'es' => 'Bielorrusia', 'fr' => 'Biélorussie', 'it' => 'Bielorussia'],
            'Belgium' => ['en' => 'Belgium', 'de' => 'Belgien', 'es' => 'Bélgica', 'fr' => 'Belgique', 'it' => 'Belgio'],
            'Belize' => ['en' => 'Belize', 'de' => 'Belize', 'es' => 'Belice', 'fr' => 'Belize', 'it' => 'Belize'],
            'Benin' => ['en' => 'Benin', 'de' => 'Benin', 'es' => 'Benín', 'fr' => 'Bénin', 'it' => 'Benin'],
            'Bhutan' => ['en' => 'Bhutan', 'de' => 'Bhutan', 'es' => 'Bután', 'fr' => 'Bhoutan', 'it' => 'Bhutan'],
            'Bolivia' => ['en' => 'Bolivia', 'de' => 'Bolivien', 'es' => 'Bolivia', 'fr' => 'Bolivie', 'it' => 'Bolivia'],
            'Bosnia and Herzegovina' => ['en' => 'Bosnia and Herzegovina', 'de' => 'Bosnien und Herzegowina', 'es' => 'Bosnia y Herzegovina', 'fr' => 'Bosnie-Herzégovine', 'it' => 'Bosnia ed Erzegovina'],
            'Botswana' => ['en' => 'Botswana', 'de' => 'Botsuana', 'es' => 'Botsuana', 'fr' => 'Botswana', 'it' => 'Botswana'],
            'Brazil' => ['en' => 'Brazil', 'de' => 'Brasilien', 'es' => 'Brasil', 'fr' => 'Brésil', 'it' => 'Brasile'],
            'Brunei' => ['en' => 'Brunei', 'de' => 'Brunei', 'es' => 'Brunéi', 'fr' => 'Brunei', 'it' => 'Brunei'],
            'Bulgaria' => ['en' => 'Bulgaria', 'de' => 'Bulgarien', 'es' => 'Bulgaria', 'fr' => 'Bulgarie', 'it' => 'Bulgaria'],
            'Burkina Faso' => ['en' => 'Burkina Faso', 'de' => 'Burkina Faso', 'es' => 'Burkina Faso', 'fr' => 'Burkina Faso', 'it' => 'Burkina Faso'],
            'Burundi' => ['en' => 'Burundi', 'de' => 'Burundi', 'es' => 'Burundi', 'fr' => 'Burundi', 'it' => 'Burundi'],
            'Cabo Verde' => ['en' => 'Cabo Verde', 'de' => 'Cabo Verde', 'es' => 'Cabo Verde', 'fr' => 'Cap-Vert', 'it' => 'Capo Verde'],
            'Cambodia' => ['en' => 'Cambodia', 'de' => 'Kambodscha', 'es' => 'Camboya', 'fr' => 'Cambodge', 'it' => 'Cambogia'],
            'Cameroon' => ['en' => 'Cameroon', 'de' => 'Kamerun', 'es' => 'Camerún', 'fr' => 'Cameroun', 'it' => 'Camerun'],
            'Canada' => ['en' => 'Canada', 'de' => 'Kanada', 'es' => 'Canadá', 'fr' => 'Canada', 'it' => 'Canada'],
            'Central African Republic' => ['en' => 'Central African Republic', 'de' => 'Zentralafrikanische Republik', 'es' => 'República Centroafricana', 'fr' => 'République centrafricaine', 'it' => 'Repubblica Centrafricana'],
            'Chad' => ['en' => 'Chad', 'de' => 'Tschad', 'es' => 'Chad', 'fr' => 'Tchad', 'it' => 'Ciad'],
            'Chile' => ['en' => 'Chile', 'de' => 'Chile', 'es' => 'Chile', 'fr' => 'Chili', 'it' => 'Cile'],
            'China' => ['en' => 'China', 'de' => 'China', 'es' => 'China', 'fr' => 'Chine', 'it' => 'Cina'],
            'Colombia' => ['en' => 'Colombia', 'de' => 'Kolumbien', 'es' => 'Colombia', 'fr' => 'Colombie', 'it' => 'Colombia'],
            'Comoros' => ['en' => 'Comoros', 'de' => 'Komoren', 'es' => 'Comoras', 'fr' => 'Comores', 'it' => 'Comore'],
            'Congo' => ['en' => 'Congo', 'de' => 'Kongo', 'es' => 'Congo', 'fr' => 'Congo', 'it' => 'Congo'],
            'Costa Rica' => ['en' => 'Costa Rica', 'de' => 'Costa Rica', 'es' => 'Costa Rica', 'fr' => 'Costa Rica', 'it' => 'Costa Rica'],
            'Croatia' => ['en' => 'Croatia', 'de' => 'Kroatien', 'es' => 'Croacia', 'fr' => 'Croatie', 'it' => 'Croazia'],
            'Cuba' => ['en' => 'Cuba', 'de' => 'Kuba', 'es' => 'Cuba', 'fr' => 'Cuba', 'it' => 'Cuba'],
            'Cyprus' => ['en' => 'Cyprus', 'de' => 'Zypern', 'es' => 'Chipre', 'fr' => 'Chypre', 'it' => 'Cipro'],
            'Czech Republic' => ['en' => 'Czech Republic', 'de' => 'Tschechische Republik', 'es' => 'República Checa', 'fr' => 'République tchèque', 'it' => 'Repubblica Ceca'],
            'Denmark' => ['en' => 'Denmark', 'de' => 'Dänemark', 'es' => 'Dinamarca', 'fr' => 'Danemark', 'it' => 'Danimarca'],
            'Djibouti' => ['en' => 'Djibouti', 'de' => 'Dschibuti', 'es' => 'Yibuti', 'fr' => 'Djibouti', 'it' => 'Gibuti'],
            'Dominica' => ['en' => 'Dominica', 'de' => 'Dominica', 'es' => 'Dominica', 'fr' => 'Dominique', 'it' => 'Dominica'],
            'Dominican Republic' => ['en' => 'Dominican Republic', 'de' => 'Dominikanische Republik', 'es' => 'República Dominicana', 'fr' => 'République dominicaine', 'it' => 'Repubblica Dominicana'],
            'Ecuador' => ['en' => 'Ecuador', 'de' => 'Ecuador', 'es' => 'Ecuador', 'fr' => 'Équateur', 'it' => 'Ecuador'],
            'Egypt' => ['en' => 'Egypt', 'de' => 'Ägypten', 'es' => 'Egipto', 'fr' => 'Égypte', 'it' => 'Egitto'],
            'El Salvador' => ['en' => 'El Salvador', 'de' => 'El Salvador', 'es' => 'El Salvador', 'fr' => 'Salvador', 'it' => 'El Salvador'],
            'Equatorial Guinea' => ['en' => 'Equatorial Guinea', 'de' => 'Äquatorialguinea', 'es' => 'Guinea Ecuatorial', 'fr' => 'Guinée équatoriale', 'it' => 'Guinea Equatoriale'],
            'Eritrea' => ['en' => 'Eritrea', 'de' => 'Eritrea', 'es' => 'Eritrea', 'fr' => 'Érythrée', 'it' => 'Eritrea'],
            'Estonia' => ['en' => 'Estonia', 'de' => 'Estland', 'es' => 'Estonia', 'fr' => 'Estonie', 'it' => 'Estonia'],
            'Eswatini' => ['en' => 'Eswatini', 'de' => 'Eswatini', 'es' => 'Esuatini', 'fr' => 'Eswatini', 'it' => 'Eswatini'],
            'Ethiopia' => ['en' => 'Ethiopia', 'de' => 'Äthiopien', 'es' => 'Etiopía', 'fr' => 'Éthiopie', 'it' => 'Etiopia'],
            'Fiji' => ['en' => 'Fiji', 'de' => 'Fidschi', 'es' => 'Fiyi', 'fr' => 'Fidji', 'it' => 'Fiji'],
            'Finland' => ['en' => 'Finland', 'de' => 'Finnland', 'es' => 'Finlandia', 'fr' => 'Finlande', 'it' => 'Finlandia'],
            'France' => ['en' => 'France', 'de' => 'Frankreich', 'es' => 'Francia', 'fr' => 'France', 'it' => 'Francia'],
            'Gabon' => ['en' => 'Gabon', 'de' => 'Gabun', 'es' => 'Gabón', 'fr' => 'Gabon', 'it' => 'Gabon'],
            'Gambia' => ['en' => 'Gambia', 'de' => 'Gambia', 'es' => 'Gambia', 'fr' => 'Gambie', 'it' => 'Gambia'],
            'Georgia' => ['en' => 'Georgia', 'de' => 'Georgien', 'es' => 'Georgia', 'fr' => 'Géorgie', 'it' => 'Georgia'],
            'Germany' => ['en' => 'Germany', 'de' => 'Deutschland', 'es' => 'Alemania', 'fr' => 'Allemagne', 'it' => 'Germania'],
            'Ghana' => ['en' => 'Ghana', 'de' => 'Ghana', 'es' => 'Ghana', 'fr' => 'Ghana', 'it' => 'Ghana'],
            'Greece' => ['en' => 'Greece', 'de' => 'Griechenland', 'es' => 'Grecia', 'fr' => 'Grèce', 'it' => 'Grecia'],
            'Grenada' => ['en' => 'Grenada', 'de' => 'Grenada', 'es' => 'Granada', 'fr' => 'Grenade', 'it' => 'Grenada'],
            'Guatemala' => ['en' => 'Guatemala', 'de' => 'Guatemala', 'es' => 'Guatemala', 'fr' => 'Guatemala', 'it' => 'Guatemala'],
            'Guinea' => ['en' => 'Guinea', 'de' => 'Guinea', 'es' => 'Guinea', 'fr' => 'Guinée', 'it' => 'Guinea'],
            'Guinea-Bissau' => ['en' => 'Guinea-Bissau', 'de' => 'Guinea-Bissau', 'es' => 'Guinea-Bisáu', 'fr' => 'Guinée-Bissau', 'it' => 'Guinea-Bissau'],
            'Guyana' => ['en' => 'Guyana', 'de' => 'Guyana', 'es' => 'Guyana', 'fr' => 'Guyana', 'it' => 'Guyana'],
            'Haiti' => ['en' => 'Haiti', 'de' => 'Haiti', 'es' => 'Haití', 'fr' => 'Haïti', 'it' => 'Haiti'],
            'Honduras' => ['en' => 'Honduras', 'de' => 'Honduras', 'es' => 'Honduras', 'fr' => 'Honduras', 'it' => 'Honduras'],
            'Hungary' => ['en' => 'Hungary', 'de' => 'Ungarn', 'es' => 'Hungría', 'fr' => 'Hongrie', 'it' => 'Ungheria'],
            'Iceland' => ['en' => 'Iceland', 'de' => 'Island', 'es' => 'Islandia', 'fr' => 'Islande', 'it' => 'Islanda'],
            'India' => ['en' => 'India', 'de' => 'Indien', 'es' => 'India', 'fr' => 'Inde', 'it' => 'India'],
            'Indonesia' => ['en' => 'Indonesia', 'de' => 'Indonesien', 'es' => 'Indonesia', 'fr' => 'Indonésie', 'it' => 'Indonesia'],
            'Iran' => ['en' => 'Iran', 'de' => 'Iran', 'es' => 'Irán', 'fr' => 'Iran', 'it' => 'Iran'],
            'Iraq' => ['en' => 'Iraq', 'de' => 'Irak', 'es' => 'Irak', 'fr' => 'Irak', 'it' => 'Iraq'],
            'Ireland' => ['en' => 'Ireland', 'de' => 'Irland', 'es' => 'Irlanda', 'fr' => 'Irlande', 'it' => 'Irlanda'],
            'Israel' => ['en' => 'Israel', 'de' => 'Israel', 'es' => 'Israel', 'fr' => 'Israël', 'it' => 'Israele'],
            'Italy' => ['en' => 'Italy', 'de' => 'Italien', 'es' => 'Italia', 'fr' => 'Italie', 'it' => 'Italia'],
            'Jamaica' => ['en' => 'Jamaica', 'de' => 'Jamaika', 'es' => 'Jamaica', 'fr' => 'Jamaïque', 'it' => 'Giamaica'],
            'Japan' => ['en' => 'Japan', 'de' => 'Japan', 'es' => 'Japón', 'fr' => 'Japon', 'it' => 'Giappone'],
            'Jordan' => ['en' => 'Jordan', 'de' => 'Jordanien', 'es' => 'Jordania', 'fr' => 'Jordanie', 'it' => 'Giordania'],
            'Kazakhstan' => ['en' => 'Kazakhstan', 'de' => 'Kasachstan', 'es' => 'Kazajistán', 'fr' => 'Kazakhstan', 'it' => 'Kazakistan'],
            'Kenya' => ['en' => 'Kenya', 'de' => 'Kenia', 'es' => 'Kenia', 'fr' => 'Kenya', 'it' => 'Kenya'],
            'Kiribati' => ['en' => 'Kiribati', 'de' => 'Kiribati', 'es' => 'Kiribati', 'fr' => 'Kiribati', 'it' => 'Kiribati'],
            'Kuwait' => ['en' => 'Kuwait', 'de' => 'Kuwait', 'es' => 'Kuwait', 'fr' => 'Koweït', 'it' => 'Kuwait'],
            'Kyrgyzstan' => ['en' => 'Kyrgyzstan', 'de' => 'Kirgisistan', 'es' => 'Kirguistán', 'fr' => 'Kirghizistan', 'it' => 'Kirghizistan'],
            'Laos' => ['en' => 'Laos', 'de' => 'Laos', 'es' => 'Laos', 'fr' => 'Laos', 'it' => 'Laos'],
            'Latvia' => ['en' => 'Latvia', 'de' => 'Lettland', 'es' => 'Letonia', 'fr' => 'Lettonie', 'it' => 'Lettonia'],
            'Lebanon' => ['en' => 'Lebanon', 'de' => 'Libanon', 'es' => 'Líbano', 'fr' => 'Liban', 'it' => 'Libano'],
            'Lesotho' => ['en' => 'Lesotho', 'de' => 'Lesotho', 'es' => 'Lesoto', 'fr' => 'Lesotho', 'it' => 'Lesotho'],
            'Liberia' => ['en' => 'Liberia', 'de' => 'Liberia', 'es' => 'Liberia', 'fr' => 'Liberia', 'it' => 'Liberia'],
            'Libya' => ['en' => 'Libya', 'de' => 'Libyen', 'es' => 'Libia', 'fr' => 'Libye', 'it' => 'Libia'],
            'Liechtenstein' => ['en' => 'Liechtenstein', 'de' => 'Liechtenstein', 'es' => 'Liechtenstein', 'fr' => 'Liechtenstein', 'it' => 'Liechtenstein'],
            'Lithuania' => ['en' => 'Lithuania', 'de' => 'Litauen', 'es' => 'Lituania', 'fr' => 'Lituanie', 'it' => 'Lituania'],
            'Luxembourg' => ['en' => 'Luxembourg', 'de' => 'Luxemburg', 'es' => 'Luxemburgo', 'fr' => 'Luxembourg', 'it' => 'Lussemburgo'],
            'Madagascar' => ['en' => 'Madagascar', 'de' => 'Madagaskar', 'es' => 'Madagascar', 'fr' => 'Madagascar', 'it' => 'Madagascar'],
            'Malawi' => ['en' => 'Malawi', 'de' => 'Malawi', 'es' => 'Malaui', 'fr' => 'Malawi', 'it' => 'Malawi'],
            'Malaysia' => ['en' => 'Malaysia', 'de' => 'Malaysia', 'es' => 'Malasia', 'fr' => 'Malaisie', 'it' => 'Malaysia'],
            'Maldives' => ['en' => 'Maldives', 'de' => 'Malediven', 'es' => 'Maldivas', 'fr' => 'Maldives', 'it' => 'Maldive'],
            'Mali' => ['en' => 'Mali', 'de' => 'Mali', 'es' => 'Malí', 'fr' => 'Mali', 'it' => 'Mali'],
            'Malta' => ['en' => 'Malta', 'de' => 'Malta', 'es' => 'Malta', 'fr' => 'Malte', 'it' => 'Malta'],
            'Marshall Islands' => ['en' => 'Marshall Islands', 'de' => 'Marshallinseln', 'es' => 'Islas Marshall', 'fr' => 'Îles Marshall', 'it' => 'Isole Marshall'],
            'Mauritania' => ['en' => 'Mauritania', 'de' => 'Mauretanien', 'es' => 'Mauritania', 'fr' => 'Mauritanie', 'it' => 'Mauritania'],
            'Mauritius' => ['en' => 'Mauritius', 'de' => 'Mauritius', 'es' => 'Mauricio', 'fr' => 'Maurice', 'it' => 'Mauritius'],
            'Mexico' => ['en' => 'Mexico', 'de' => 'Mexiko', 'es' => 'México', 'fr' => 'Mexique', 'it' => 'Messico'],
            'Micronesia' => ['en' => 'Micronesia', 'de' => 'Mikronesien', 'es' => 'Micronesia', 'fr' => 'Micronésie', 'it' => 'Micronesia'],
            'Moldova' => ['en' => 'Moldova', 'de' => 'Moldau', 'es' => 'Moldavia', 'fr' => 'Moldavie', 'it' => 'Moldavia'],
            'Monaco' => ['en' => 'Monaco', 'de' => 'Monaco', 'es' => 'Mónaco', 'fr' => 'Monaco', 'it' => 'Monaco'],
            'Mongolia' => ['en' => 'Mongolia', 'de' => 'Mongolei', 'es' => 'Mongolia', 'fr' => 'Mongolie', 'it' => 'Mongolia'],
            'Montenegro' => ['en' => 'Montenegro', 'de' => 'Montenegro', 'es' => 'Montenegro', 'fr' => 'Monténégro', 'it' => 'Montenegro'],
            'Morocco' => ['en' => 'Morocco', 'de' => 'Marokko', 'es' => 'Marruecos', 'fr' => 'Maroc', 'it' => 'Marocco'],
            'Mozambique' => ['en' => 'Mozambique', 'de' => 'Mosambik', 'es' => 'Mozambique', 'fr' => 'Mozambique', 'it' => 'Mozambico'],
            'Myanmar' => ['en' => 'Myanmar', 'de' => 'Myanmar', 'es' => 'Myanmar', 'fr' => 'Birmanie', 'it' => 'Birmania'],
            'Namibia' => ['en' => 'Namibia', 'de' => 'Namibia', 'es' => 'Namibia', 'fr' => 'Namibie', 'it' => 'Namibia'],
            'Nauru' => ['en' => 'Nauru', 'de' => 'Nauru', 'es' => 'Nauru', 'fr' => 'Nauru', 'it' => 'Nauru'],
            'Nepal' => ['en' => 'Nepal', 'de' => 'Nepal', 'es' => 'Nepal', 'fr' => 'Népal', 'it' => 'Nepal'],
            'Netherlands' => ['en' => 'Netherlands', 'de' => 'Niederlande', 'es' => 'Países Bajos', 'fr' => 'Pays-Bas', 'it' => 'Paesi Bassi'],
            'New Zealand' => ['en' => 'New Zealand', 'de' => 'Neuseeland', 'es' => 'Nueva Zelanda', 'fr' => 'Nouvelle-Zélande', 'it' => 'Nuova Zelanda'],
            'Nicaragua' => ['en' => 'Nicaragua', 'de' => 'Nicaragua', 'es' => 'Nicaragua', 'fr' => 'Nicaragua', 'it' => 'Nicaragua'],
            'Niger' => ['en' => 'Niger', 'de' => 'Niger', 'es' => 'Níger', 'fr' => 'Niger', 'it' => 'Niger'],
            'Nigeria' => ['en' => 'Nigeria', 'de' => 'Nigeria', 'es' => 'Nigeria', 'fr' => 'Nigeria', 'it' => 'Nigeria'],
            'North Korea' => ['en' => 'North Korea', 'de' => 'Nordkorea', 'es' => 'Corea del Norte', 'fr' => 'Corée du Nord', 'it' => 'Corea del Nord'],
            'North Macedonia' => ['en' => 'North Macedonia', 'de' => 'Nordmazedonien', 'es' => 'Macedonia del Norte', 'fr' => 'Macédoine du Nord', 'it' => 'Macedonia del Nord'],
            'Norway' => ['en' => 'Norway', 'de' => 'Norwegen', 'es' => 'Noruega', 'fr' => 'Norvège', 'it' => 'Norvegia'],
            'Oman' => ['en' => 'Oman', 'de' => 'Oman', 'es' => 'Omán', 'fr' => 'Oman', 'it' => 'Oman'],
            'Pakistan' => ['en' => 'Pakistan', 'de' => 'Pakistan', 'es' => 'Pakistán', 'fr' => 'Pakistan', 'it' => 'Pakistan'],
            'Palau' => ['en' => 'Palau', 'de' => 'Palau', 'es' => 'Palaos', 'fr' => 'Palaos', 'it' => 'Palau'],
            'Palestine' => ['en' => 'Palestine', 'de' => 'Palästina', 'es' => 'Palestina', 'fr' => 'Palestine', 'it' => 'Palestina'],
            'Panama' => ['en' => 'Panama', 'de' => 'Panama', 'es' => 'Panamá', 'fr' => 'Panama', 'it' => 'Panama'],
            'Papua New Guinea' => ['en' => 'Papua New Guinea', 'de' => 'Papua-Neuguinea', 'es' => 'Papúa Nueva Guinea', 'fr' => 'Papouasie-Nouvelle-Guinée', 'it' => 'Papua Nuova Guinea'],
            'Paraguay' => ['en' => 'Paraguay', 'de' => 'Paraguay', 'es' => 'Paraguay', 'fr' => 'Paraguay', 'it' => 'Paraguay'],
            'Peru' => ['en' => 'Peru', 'de' => 'Peru', 'es' => 'Perú', 'fr' => 'Pérou', 'it' => 'Perù'],
            'Philippines' => ['en' => 'Philippines', 'de' => 'Philippinen', 'es' => 'Filipinas', 'fr' => 'Philippines', 'it' => 'Filippine'],
            'Poland' => ['en' => 'Poland', 'de' => 'Polen', 'es' => 'Polonia', 'fr' => 'Pologne', 'it' => 'Polonia'],
            'Portugal' => ['en' => 'Portugal', 'de' => 'Portugal', 'es' => 'Portugal', 'fr' => 'Portugal', 'it' => 'Portogallo'],
            'Qatar' => ['en' => 'Qatar', 'de' => 'Katar', 'es' => 'Catar', 'fr' => 'Qatar', 'it' => 'Qatar'],
            'Romania' => ['en' => 'Romania', 'de' => 'Rumänien', 'es' => 'Rumania', 'fr' => 'Roumanie', 'it' => 'Romania'],
            'Russia' => ['en' => 'Russia', 'de' => 'Russland', 'es' => 'Rusia', 'fr' => 'Russie', 'it' => 'Russia'],
            'Rwanda' => ['en' => 'Rwanda', 'de' => 'Ruanda', 'es' => 'Ruanda', 'fr' => 'Rwanda', 'it' => 'Ruanda'],
            'Saint Kitts and Nevis' => ['en' => 'Saint Kitts and Nevis', 'de' => 'St. Kitts und Nevis', 'es' => 'San Cristóbal y Nieves', 'fr' => 'Saint-Kitts-et-Nevis', 'it' => 'Saint Kitts e Nevis'],
            'Saint Lucia' => ['en' => 'Saint Lucia', 'de' => 'St. Lucia', 'es' => 'Santa Lucía', 'fr' => 'Sainte-Lucie', 'it' => 'Santa Lucia'],
            'Saint Vincent and the Grenadines' => ['en' => 'Saint Vincent and the Grenadines', 'de' => 'St. Vincent und die Grenadinen', 'es' => 'San Vicente y las Granadinas', 'fr' => 'Saint-Vincent-et-les-Grenadines', 'it' => 'Saint Vincent e Grenadine'],
            'Samoa' => ['en' => 'Samoa', 'de' => 'Samoa', 'es' => 'Samoa', 'fr' => 'Samoa', 'it' => 'Samoa'],
            'San Marino' => ['en' => 'San Marino', 'de' => 'San Marino', 'es' => 'San Marino', 'fr' => 'Saint-Marin', 'it' => 'San Marino'],
            'Sao Tome and Principe' => ['en' => 'Sao Tome and Principe', 'de' => 'São Tomé und Príncipe', 'es' => 'Santo Tomé y Príncipe', 'fr' => 'Sao Tomé-et-Principe', 'it' => 'São Tomé e Príncipe'],
            'Saudi Arabia' => ['en' => 'Saudi Arabia', 'de' => 'Saudi-Arabien', 'es' => 'Arabia Saudita', 'fr' => 'Arabie saoudite', 'it' => 'Arabia Saudita'],
            'Senegal' => ['en' => 'Senegal', 'de' => 'Senegal', 'es' => 'Senegal', 'fr' => 'Sénégal', 'it' => 'Senegal'],
            'Serbia' => ['en' => 'Serbia', 'de' => 'Serbien', 'es' => 'Serbia', 'fr' => 'Serbie', 'it' => 'Serbia'],
            'Seychelles' => ['en' => 'Seychelles', 'de' => 'Seychellen', 'es' => 'Seychelles', 'fr' => 'Seychelles', 'it' => 'Seychelles'],
            'Sierra Leone' => ['en' => 'Sierra Leone', 'de' => 'Sierra Leone', 'es' => 'Sierra Leona', 'fr' => 'Sierra Leone', 'it' => 'Sierra Leone'],
            'Singapore' => ['en' => 'Singapore', 'de' => 'Singapur', 'es' => 'Singapur', 'fr' => 'Singapour', 'it' => 'Singapore'],
            'Slovakia' => ['en' => 'Slovakia', 'de' => 'Slowakei', 'es' => 'Eslovaquia', 'fr' => 'Slovaquie', 'it' => 'Slovacchia'],
            'Slovenia' => ['en' => 'Slovenia', 'de' => 'Slowenien', 'es' => 'Eslovenia', 'fr' => 'Slovénie', 'it' => 'Slovenia'],
            'Solomon Islands' => ['en' => 'Solomon Islands', 'de' => 'Salomonen', 'es' => 'Islas Salomón', 'fr' => 'Îles Salomon', 'it' => 'Isole Salomone'],
            'Somalia' => ['en' => 'Somalia', 'de' => 'Somalia', 'es' => 'Somalia', 'fr' => 'Somalie', 'it' => 'Somalia'],
            'South Africa' => ['en' => 'South Africa', 'de' => 'Südafrika', 'es' => 'Sudáfrica', 'fr' => 'Afrique du Sud', 'it' => 'Sudafrica'],
            'South Korea' => ['en' => 'South Korea', 'de' => 'Südkorea', 'es' => 'Corea del Sur', 'fr' => 'Corée du Sud', 'it' => 'Corea del Sud'],
            'South Sudan' => ['en' => 'South Sudan', 'de' => 'Südsudan', 'es' => 'Sudán del Sur', 'fr' => 'Soudan du Sud', 'it' => 'Sudan del Sud'],
            'Spain' => ['en' => 'Spain', 'de' => 'Spanien', 'es' => 'España', 'fr' => 'Espagne', 'it' => 'Spagna'],
            'Sri Lanka' => ['en' => 'Sri Lanka', 'de' => 'Sri Lanka', 'es' => 'Sri Lanka', 'fr' => 'Sri Lanka', 'it' => 'Sri Lanka'],
            'Sudan' => ['en' => 'Sudan', 'de' => 'Sudan', 'es' => 'Sudán', 'fr' => 'Soudan', 'it' => 'Sudan'],
            'Suriname' => ['en' => 'Suriname', 'de' => 'Suriname', 'es' => 'Surinam', 'fr' => 'Suriname', 'it' => 'Suriname'],
            'Sweden' => ['en' => 'Sweden', 'de' => 'Schweden', 'es' => 'Suecia', 'fr' => 'Suède', 'it' => 'Svezia'],
            'Switzerland' => ['en' => 'Switzerland', 'de' => 'Schweiz', 'es' => 'Suiza', 'fr' => 'Suisse', 'it' => 'Svizzera'],
            'Syria' => ['en' => 'Syria', 'de' => 'Syrien', 'es' => 'Siria', 'fr' => 'Syrie', 'it' => 'Siria'],
            'Taiwan' => ['en' => 'Taiwan', 'de' => 'Taiwan', 'es' => 'Taiwán', 'fr' => 'Taïwan', 'it' => 'Taiwan'],
            'Tajikistan' => ['en' => 'Tajikistan', 'de' => 'Tadschikistan', 'es' => 'Tayikistán', 'fr' => 'Tadjikistan', 'it' => 'Tagikistan'],
            'Tanzania' => ['en' => 'Tanzania', 'de' => 'Tansania', 'es' => 'Tanzania', 'fr' => 'Tanzanie', 'it' => 'Tanzania'],
            'Thailand' => ['en' => 'Thailand', 'de' => 'Thailand', 'es' => 'Tailandia', 'fr' => 'Thaïlande', 'it' => 'Tailandia'],
            'Timor-Leste' => ['en' => 'Timor-Leste', 'de' => 'Timor-Leste', 'es' => 'Timor Oriental', 'fr' => 'Timor oriental', 'it' => 'Timor Est'],
            'Togo' => ['en' => 'Togo', 'de' => 'Togo', 'es' => 'Togo', 'fr' => 'Togo', 'it' => 'Togo'],
            'Tonga' => ['en' => 'Tonga', 'de' => 'Tonga', 'es' => 'Tonga', 'fr' => 'Tonga', 'it' => 'Tonga'],
            'Trinidad and Tobago' => ['en' => 'Trinidad and Tobago', 'de' => 'Trinidad und Tobago', 'es' => 'Trinidad y Tobago', 'fr' => 'Trinité-et-Tobago', 'it' => 'Trinidad e Tobago'],
            'Tunisia' => ['en' => 'Tunisia', 'de' => 'Tunesien', 'es' => 'Túnez', 'fr' => 'Tunisie', 'it' => 'Tunisia'],
            'Turkey' => ['en' => 'Turkey', 'de' => 'Türkei', 'es' => 'Turquía', 'fr' => 'Turquie', 'it' => 'Turchia'],
            'Turkmenistan' => ['en' => 'Turkmenistan', 'de' => 'Turkmenistan', 'es' => 'Turkmenistán', 'fr' => 'Turkménistan', 'it' => 'Turkmenistan'],
            'Tuvalu' => ['en' => 'Tuvalu', 'de' => 'Tuvalu', 'es' => 'Tuvalu', 'fr' => 'Tuvalu', 'it' => 'Tuvalu'],
            'Uganda' => ['en' => 'Uganda', 'de' => 'Uganda', 'es' => 'Uganda', 'fr' => 'Ouganda', 'it' => 'Uganda'],
            'Ukraine' => ['en' => 'Ukraine', 'de' => 'Ukraine', 'es' => 'Ucrania', 'fr' => 'Ukraine', 'it' => 'Ucraina'],
            'United Arab Emirates' => ['en' => 'United Arab Emirates', 'de' => 'Vereinigte Arabische Emirate', 'es' => 'Emiratos Árabes Unidos', 'fr' => 'Émirats arabes unis', 'it' => 'Emirati Arabi Uniti'],
            'United Kingdom' => ['en' => 'United Kingdom', 'de' => 'Vereinigtes Königreich', 'es' => 'Reino Unido', 'fr' => 'Royaume-Uni', 'it' => 'Regno Unito'],
            'United States' => ['en' => 'United States', 'de' => 'Vereinigte Staaten', 'es' => 'Estados Unidos', 'fr' => 'États-Unis', 'it' => 'Stati Uniti'],
            'Uruguay' => ['en' => 'Uruguay', 'de' => 'Uruguay', 'es' => 'Uruguay', 'fr' => 'Uruguay', 'it' => 'Uruguay'],
            'Uzbekistan' => ['en' => 'Uzbekistan', 'de' => 'Usbekistan', 'es' => 'Uzbekistán', 'fr' => 'Ouzbékistan', 'it' => 'Uzbekistan'],
            'Vanuatu' => ['en' => 'Vanuatu', 'de' => 'Vanuatu', 'es' => 'Vanuatu', 'fr' => 'Vanuatu', 'it' => 'Vanuatu'],
            'Vatican City' => ['en' => 'Vatican City', 'de' => 'Vatikanstadt', 'es' => 'Ciudad del Vaticano', 'fr' => 'Cité du Vatican', 'it' => 'Città del Vaticano'],
            'Venezuela' => ['en' => 'Venezuela', 'de' => 'Venezuela', 'es' => 'Venezuela', 'fr' => 'Venezuela', 'it' => 'Venezuela'],
            'Vietnam' => ['en' => 'Vietnam', 'de' => 'Vietnam', 'es' => 'Vietnam', 'fr' => 'Viêt Nam', 'it' => 'Vietnam'],
            'Yemen' => ['en' => 'Yemen', 'de' => 'Jemen', 'es' => 'Yemen', 'fr' => 'Yémen', 'it' => 'Yemen'],
            'Zambia' => ['en' => 'Zambia', 'de' => 'Sambia', 'es' => 'Zambia', 'fr' => 'Zambie', 'it' => 'Zambia'],
            'Zimbabwe' => ['en' => 'Zimbabwe', 'de' => 'Simbabwe', 'es' => 'Zimbabue', 'fr' => 'Zimbabwe', 'it' => 'Zimbabwe'],
        ];
    }
}