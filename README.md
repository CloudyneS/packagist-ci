# PHP Packagist CI
Upload artifact packages to private packagist

## Options
Note: All options are required

Package versions cannot be overwritten, but when pushing dev-* versions the script replaces the version artifacts with the new one.
```bash
CMP_API_KEY=pAcKaGiStKeY
CMP_API_SECRET=PaCkAgIsTsEcReT
CMP_PACKAGE_NAME=namespace/package-name
CMP_PACKAGE_PATH=/path/to/package.zip
CMP_PACKAGE_VERSION="1.0.0"
```