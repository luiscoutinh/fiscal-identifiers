# Brazil (`BR`)

Brazil exposes two local identifier types:

- `cpf` — Cadastro de Pessoas Físicas
- `cnpj` — Cadastro Nacional da Pessoa Jurídica

The jurisdiction also declares subject-to-type mappings:

- `person` -> `cpf`
- `company` -> `cnpj`

There is intentionally no unconditional country-level default because CPF and CNPJ are different schemes. Applications that predominantly work with one subject kind can configure a default subject globally. For example, a business-oriented application can use `company`, causing an omitted identifier type for `BR` to resolve to `cnpj`.

```php
use FiscalIdentifiers\Configuration\IdentifierResolutionConfiguration;
use FiscalIdentifiers\FiscalIdentifierValidator;

$validator = new FiscalIdentifierValidator(
    $countries,
    new IdentifierResolutionConfiguration(defaultSubject: 'company'),
);

$validator->validate('BR', '12.ABC.345/01DE-35');        // resolves to cnpj
$validator->validateFor('BR', '999.999.990-50', 'person'); // resolves to cpf
$validator->validate('BR', '999.999.990-50', 'cpf');       // explicit low-level type
```

Explicit identifier type always remains available and is the precise API when subject alone is not enough to identify a scheme.

## CPF

The local validator:

1. removes permitted presentation separators (spaces, `.` and `-`);
2. requires exactly 11 decimal digits;
3. rejects repeated-digit placeholders such as `111.111.111-11`;
4. validates the two check digits using the official modulo-11 rule.

A successful result establishes structural/checksum consistency only. It does not establish that a CPF was assigned, is active, or belongs to a particular person.

## CNPJ

The local validator supports both formats that coexist in Brazil:

- legacy numeric CNPJ;
- alphanumeric CNPJ introduced for new registrations in 2026.

Normalization removes spaces and the presentation separators `.`, `/` and `-`, and uppercases letters. The canonical form contains 12 alphanumeric base characters followed by two numeric check digits.

The check digits use modulo 11. Character values are derived from their ASCII value minus 48, as specified by Receita Federal. The first digit uses weights `5,4,3,2,9,8,7,6,5,4,3,2`; the second uses `6,5,4,3,2,9,8,7,6,5,4,3,2` after appending the first digit.

Existing numeric CNPJs remain valid and coexist with alphanumeric registrations.

## Sources

Authoritative sources reviewed on 2026-09-16:

- Receita Federal — CNPJ Alfanumérico: https://www.gov.br/receitafederal/pt-br/acesso-a-informacao/acoes-e-programas/programas-e-atividades/cnpj-alfanumerico/cnpj-alfa
- Receita Federal / SERPRO — Cálculo dos dígitos verificadores de CNPJ alfanumérico: https://www.gov.br/receitafederal/pt-br/centrais-de-conteudo/publicacoes/documentos-tecnicos/cnpj/manual-dv-cnpj.pdf
- Receita Federal — e-Financeira validation rules for CPF modulo-11 check digits: https://www.gov.br/receitafederal/pt-br/centrais-de-conteudo/publicacoes/manuais/sped/manuais-e-financeira/versao-2.x/manual-e-financeira-anexo-v-versao-2-0-1-leiaute-modulo-de-repasse.pdf
- Brazilian public-sector validation guidance explicitly rejecting equal-digit CPF/CNPJ placeholders: https://www.gov.br/previdencia/pt-br/outros/imagens/2015/07/rgrva_RegrasValidacao.pdf

The package does not perform Receita Federal registry/existence checks in `validate()`; such checks belong to a separate authoritative-verification layer.
