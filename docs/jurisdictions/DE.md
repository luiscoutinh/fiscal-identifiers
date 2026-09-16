# Germany (`DE`)

Germany has several fiscal identifiers with different purposes. They are intentionally modeled as separate identifier types rather than treated as aliases.

Current package types:

- `idnr` — personal tax identification number (`steuerliche Identifikationsnummer`, IdNr): 11 digits;
- `widnr` — business/economic identification number (`Wirtschafts-Identifikationsnummer`, W-IdNr.): `DE` + 9 digits + a 5-digit distinguishing feature;
- `ust_idnr` — VAT identification number (`Umsatzsteuer-Identifikationsnummer`, USt-IdNr.): `DE` + 9 digits;
- `steuernummer` — the standardized federal (`Bundesschema`) tax-number representation: 13 digits.

## Subject resolution

The package maps the general subjects as follows:

```text
person  -> idnr
company -> widnr
```

`widnr` is used as the general company/economic identifier because the W-IdNr. is the nationwide identifier intended for economically active persons and entities and for cross-register identification. It does **not** replace the USt-IdNr. or the Steuernummer.

A company-oriented application can therefore use:

```php
new IdentifierResolutionConfiguration(defaultSubject: 'company')
```

and then:

```php
$validator->validate('DE', $value);
```

which resolves to `widnr`.

The rollout is transitional. Current Federal Ministry of Finance guidance says existing businesses receive W-IdNr. assignments progressively, so callers that need another German scheme should request it explicitly.

## Personal IdNr

The German personal tax IdNr is an 11-digit number assigned to natural persons. Official BZSt material states that it consists of ten digits plus a check digit and remains associated with the person for life.

This implementation currently validates the documented 11-digit structure only. The checksum step is reported as `not_supported` until the package has an authoritative public specification of the check-digit algorithm suitable for implementation.

## W-IdNr

The W-IdNr. consists of:

- `DE`;
- nine digits;
- a five-digit distinguishing feature (`Unterscheidungsmerkmal`) appended with a hyphen.

Official examples use forms such as `DE123456789-00001`. Additional distinguishing features can identify multiple economic activities, businesses or establishments belonging to the same economically active subject.

Where a business already had a USt-IdNr. during the introduction of W-IdNr., official guidance states that the nine-digit base corresponds to that USt-IdNr.; the W-IdNr. adds the distinguishing feature. The identifiers nevertheless remain different in purpose and are modeled separately.

The current validator checks this documented structure and does not perform registry/existence verification.

## USt-IdNr

The German VAT identification number consists of the country prefix `DE` followed by nine digits. It is used specifically for VAT-related identification, including relevant intra-EU activity.

It is not treated as Germany's general company identifier, and local validation does not establish that the identifier is currently registered for VAT purposes. Any authoritative VAT-status check belongs to a separate verification layer.

## Steuernummer

German tax offices also use the Steuernummer. BZSt documentation defines a standardized nationwide 13-digit representation (`Bundesschema`) and documents how state-specific formats map into it.

This first implementation accepts the 13-digit federal representation only. It deliberately does not strip slashes from arbitrary state-specific forms because those formats require state-aware conversion rather than separator removal.

## Sources

Authoritative sources reviewed on 2026-09-16:

- Federal Ministry of Finance — W-IdNr. overview and relationship to USt-IdNr.: https://www.bundesfinanzministerium.de/Monatsberichte/Ausgabe/2024/11/Inhalte/Kapitel-3-Analysen/3-2-wirtschafts-identifikationsnummer-startet.html
- Federal Ministry of Finance — current W-IdNr. FAQ: https://www.bundesfinanzministerium.de/Content/DE/FAQ/wirtschafts-identifikationsnummer.html
- BZSt — public notice on W-IdNr. introduction and structure: https://www.bzst.de/SharedDocs/Downloads/DE/WIdNr/oeffentliche_bekanntmachung_widnr.pdf?__blob=publicationFile&v=1
- BZSt — USt-IdNr. structure in EU Member States: https://www.bzst.de/SharedDocs/Downloads/DE/Merkblaetter/ust_idnr_aufbau.pdf?__blob=publicationFile&v=2
- BZSt — personal IdNr. documentation: https://www.bzst.de/SharedDocs/Downloads/DE/ELStAM/Kommunikationshandbuch_ELStAM_Teil_3.pdf?__blob=publicationFile&v=5
- BZSt — standardized federal Steuernummer scheme: https://www.bzst.de/SharedDocs/Downloads/DE/WIdNr/AufbauSteuernummerBundesschema.pdf?__blob=publicationFile&v=1

As with all jurisdictions in this package, local validation does not prove assignment, ownership, current registry status or VAT registration.
