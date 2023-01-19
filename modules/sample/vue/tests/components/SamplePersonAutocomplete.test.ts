/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

import { OxTest } from "oxify"
import SamplePersonAutocomplete from "@modules/sample/vue/components/SamplePersonAutocomplete/SamplePersonAutocomplete.vue"
import { Component } from "vue"
import { createLocalVue, shallowMount } from "@vue/test-utils"
import pinia from "@/core/plugins/OxPiniaCore"
import OxTranslator from "@/core/plugins/OxTranslator"
import SamplePerson from "@modules/sample/vue/models/SamplePerson"
import { storeSchemas } from "@/core/utils/OxStorage"
import { OxSchema } from "@/core/types/OxSchema"
import { setActivePinia } from "pinia"

const localVue = createLocalVue()
localVue.use(OxTranslator)

/* eslint-disable dot-notation */

/**
 * Test pour SamplePersonAutocomplete
 */
export default class SamplePersonAutocompleteTest extends OxTest {
    protected component = SamplePersonAutocomplete

    private actor = new SamplePerson()

    protected beforeAllTests () {
        const schema = [
            {
                id: "489f3046fbdf81481652a4b19b45a25c",
                owner: "sample_person",
                field: "last_name",
                type: "str",
                fieldset: "default",
                autocomplete: null,
                placeholder: null,
                notNull: true,
                confidential: null,
                default: null,
                libelle: "Nom",
                label: "Nom",
                description: "Nom de famille"
            },
            {
                id: "9b835cdbdf32d4ba811093336b069970",
                owner: "sample_person",
                field: "first_name",
                type: "str",
                fieldset: "default",
                autocomplete: null,
                placeholder: null,
                notNull: true,
                confidential: null,
                default: null,
                libelle: "Prnom",
                label: "Prnom",
                description: "Prnom"
            },
            {
                id: "0f18b60317532cf3493bc79132667421",
                owner: "sample_person",
                field: "is_director",
                type: "bool",
                fieldset: "default",
                autocomplete: null,
                placeholder: null,
                notNull: null,
                confidential: null,
                default: "0",
                libelle: "Ralisateur",
                label: "Ralisateur",
                description: "Est un ralisateur"
            },
            {
                id: "6f9a18d762968f4c7af7d50581bee48f",
                owner: "sample_person",
                field: "birthdate",
                type: "birthDate",
                fieldset: "extra",
                autocomplete: null,
                placeholder: "99/99/9999",
                notNull: null,
                confidential: null,
                default: null,
                libelle: "Date de naissance",
                label: "Naissance",
                description: "Date de naissance"
            },
            {
                id: "b187c3c5dca8287f28623e931943509a",
                owner: "sample_person",
                field: "sex",
                type: "enum",
                fieldset: "extra",
                autocomplete: null,
                placeholder: null,
                notNull: null,
                confidential: null,
                default: null,
                values: [
                    "m",
                    "f"
                ],
                translations: {
                    m: "Masculin",
                    f: "Fminin"
                },
                libelle: "Sexe",
                label: "Sexe",
                description: "Sexe"
            },
            {
                id: "1c1f00ed017758b7de04174e5a21177f",
                owner: "sample_person",
                field: "activity_start",
                type: "date",
                fieldset: "extra",
                autocomplete: null,
                placeholder: null,
                notNull: null,
                confidential: null,
                default: null,
                libelle: "Dbut d'activit",
                label: "Dbut d'activit",
                description: "Date de dbut d'activit"
            }
        ] as unknown as OxSchema[]

        this.actor.id = "185"
        this.actor.type = "sample_person"
        this.actor.attributes = {
            last_name: "Lambertini",
            first_name: "Lucia",
            is_director: false,
            birthdate: "1926-06-26",
            sex: "f",
            activity_start: "1998-03-03"
        }
        this.actor.relationships = {
            nationality: {
                data: {
                    type: "sample_nationality",
                    id: "17"
                }
            }
        }
        this.actor.links = {
            self: "/api/sample/persons/185",
            schema: "/api/schemas/sample_person?fieldsets=default,extra",
            history: "/api/history/sample_person/185",
            profile_picture: "?m=files&raw=thumbnail&document_id=36909&thumb=0"
        }
        this.actor.meta = {
            permissions: {
                perm: "edit"
            }
        }

        setActivePinia(pinia)
        storeSchemas(schema)
    }

    /**
     * @inheritDoc
     */
    protected mountComponent (
        props: object = {},
        stubs: { [key: string]: Component | string | boolean } | string[] = {},
        slots: { [key: string]: (Component | string)[] | Component | string } = {}
    ) {
        return shallowMount(
            this.component,
            {
                propsData: props,
                mocks: {},
                slots: slots,
                stubs: stubs,
                methods: {},
                localVue,
                pinia
            }
        )
    }

    /**
     * @inheritDoc
     */
    protected vueComponent (
        props: object = {},
        stubs: { [key: string]: Component | string | boolean } | string[] = {},
        slots: { [key: string]: (Component | string)[] | Component | string } = {}
    ) {
        return this.mountComponent(props, stubs, slots).vm
    }

    public testSampleActorLineDefault () {
        const actorComponent = this.mountComponent({ person: this.actor })

        this.assertEqual(actorComponent.vm["person"].fullName, "Lucia Lambertini")
    }
}

(new SamplePersonAutocompleteTest()).launchTests()
