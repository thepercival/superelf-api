# SuperElf API

## Indeling van vijf bekerrondes

De beker heeft vijf rondes bij 17 tot en met 32 geldige pooldeelnemers. Iedere
bekerronde bestaat per tweestrijd uit drie wedstrijden, verspreid over drie
chronologisch opeenvolgende wedstrijdrondes van de Eredivisie. Deze waarde is
vastgelegd als `BaseCreator::NrOfAgainstGamesPerRound`.

De wedstrijdrondes worden bepaald aan de hand van de wedstrijden van de
Eredivisie die binnen de viewperiode vallen. Deze worden op aanvangstijd
oplopend opgehaald en als unieke wedstrijdronden aan de viewperiode gekoppeld.

Voor vijf bekerrondes verdeelt `CupCreator` de rondes als volgt:

| Viewperiode | Posities van Eredivisie-wedstrijdrondes | Gebruik |
|---|---:|---|
| Assemble | 1 | overgeslagen |
| Assemble | 2-4 | gereserveerd voor de SuperCup |
| Assemble | 5 | overgeslagen |
| Assemble | 6-8 | eerste bekerronde |
| Assemble | 9 | overgeslagen |
| Assemble | 10-12 | tweede bekerronde |
| Assemble | 13 | overgeslagen |
| Assemble | 14-16 | kwartfinale |
| Transfer | 1-3 | niet gebruikt voor de beker |
| Transfer | 4-6 | halve finale |
| Transfer | 7 | overgeslagen |
| Transfer | 8-10 | finale |
| Transfer | 11-12 | niet gebruikt voor de beker |

Daaruit volgen deze voorwaarden:

- De eerste twee bekerrondes vinden plaats in de assemble-viewperiode. De halve
  finale en finale vinden plaats in de transfer-viewperiode.
- Als na de tweede bekerronde in assemble nog minimaal vier wedstrijdrondes
  beschikbaar zijn, wordt één ronde overgeslagen en vindt de derde bekerronde
  plaats in de volgende drie rondes. Hiervoor zijn minimaal 16 assemble-rondes
  en 9 transfer-rondes nodig.
- Als assemble die ruimte niet heeft, blijft de oude verdeling actief: de derde
  bekerronde vindt dan plaats in transfer. Hiervoor zijn minimaal 12
  assemble-rondes en 13 transfer-rondes nodig.
- Elke toegewezen groep moet drie wedstrijdrondes bevatten. Als een groep niet
  volledig kan worden samengesteld, stopt het aanmaken met
  `not enough gameRounds`.
- De grenzen van de viewperiodes moeten zo zijn ingesteld dat de gewenste
  Eredivisie-wedstrijden daadwerkelijk binnen de betreffende periode vallen.

De verdeling zelf staat in
[`domain/Competitions/CupCreator.php`](domain/Competitions/CupCreator.php). Het
aantal wedstrijden per bekerronde staat in
[`domain/Competitions/BaseCreator.php`](domain/Competitions/BaseCreator.php).
De chronologische selectie van Eredivisie-wedstrijdrondes staat in
[`app/Repositories/Sports/AgainstGameRepository.php`](app/Repositories/Sports/AgainstGameRepository.php)
en de koppeling aan viewperiodes in
[`app/Syncers/SuperElfGameRoundSyncer.php`](app/Syncers/SuperElfGameRoundSyncer.php).