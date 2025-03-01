import eslint from "@eslint/js";
import tslint from "typescript-eslint";
import globals from "globals";
import stylistic from "@stylistic/eslint-plugin";

export default tslint.config(
	eslint.configs.recommended,
	tslint.configs.strict,
	tslint.configs.stylistic,
	stylistic.configs.recommended,
	{
		plugins: {
			"@stylistic": stylistic
		},
		languageOptions: {
			globals: {
				...globals.node,
				...globals.browser
			},
			parserOptions: {
				project: [ "./tsconfig.json" ]
			}
		},
		rules: {
			// Règles de base
			"no-shadow": "off",
			"camelcase": [
				"error",
				{
					properties: "never",
					ignoreImports: true,
					ignoreDestructuring: false
				}
			],
			"no-unused-vars": "off",
			"no-param-reassign": [
				"error",
				{
					props: false
				}
			],

			// Règles de style
			"@stylistic/semi": [ "error", "always" ],
			"@stylistic/indent": [
				"error",
				"tab",
				{
					SwitchCase: 1
				}
			],
			"@stylistic/quotes": [ "error", "double" ],
			"@stylistic/no-tabs": [
				"error",
				{
					allowIndentationTabs: true
				}
			],
			"@stylistic/eol-last": [ "error", "never" ],
			"@stylistic/brace-style": [
				"error",
				"allman",
				{
					allowSingleLine: true
				}
			],
			"@stylistic/arrow-parens": [ "error", "always" ],
			"@stylistic/comma-dangle": [
				"error",
				{
					arrays: "never",
					objects: "never",
					imports: "never",
					exports: "never",
					functions: "never",
					importAttributes: "never",
					dynamicImports: "never"
				}
			],
			"@stylistic/object-curly-newline": [
				"error",
				{
					ImportDeclaration: {
						minProperties: 0
					}
				}
			],
			"@stylistic/comma-style": [ "error", "last" ],
			"@stylistic/space-in-parens": [ "error", "always" ],
			"@stylistic/array-bracket-spacing": [ "error", "always" ],
			"@stylistic/template-curly-spacing": [ "error", "always" ],
			"@stylistic/member-delimiter-style": [
				"error",
				{
					overrides: {
						interface: {
							multiline: {
								delimiter: "semi",
								requireLast: true
							}
						}
					}
				}
			],
			"@stylistic/computed-property-spacing": [ "error", "always" ],

			// Règles pour TypeScript ESLint
			"@typescript-eslint/no-empty-function": "off",
			"@typescript-eslint/no-dynamic-delete": "off" // https://github.com/typescript-eslint/typescript-eslint/issues/3504
		}
	}
);