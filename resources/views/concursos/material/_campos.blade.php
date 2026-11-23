<label>Assunto <input name="titulo" required maxlength="200" value="{{ old('titulo', $p?->titulo) }}" placeholder="Decadência com fraude: art. 173, I"></label>
<label>Onde caio (a pegadinha) <textarea name="armadilha" maxlength="5000">{{ old('armadilha', $p?->armadilha) }}</textarea></label>
<label>Regra certa <textarea name="regra" required maxlength="8000">{{ old('regra', $p?->regra) }}</textarea></label>
<label>Macete <textarea name="macete" maxlength="3000">{{ old('macete', $p?->macete) }}</textarea></label>
<label>Base legal <input name="base_legal" maxlength="2000" value="{{ old('base_legal', $p?->base_legal) }}" placeholder="[[ctn 173, I]] · Súmula 555/STJ"></label>
<label>Questões do TEC <input name="questoes" maxlength="500" value="{{ old('questoes', implode(', ', $p?->questoes ?? [])) }}" placeholder="3846934, 3863215"></label>
<p class="dica">Aceita Markdown (**negrito**, listas com "- "). Para linkar a lei: [[ctn 98]], [[cf 155 §6, III]], [[adct 136]]; qualquer norma federal: [[lei8137 1]], [[lcp118 3]], [[dec1171 1]]. Jurisprudência: [[sv 24]], [[sumstf 160]], [[sumstj 392]], [[temastf 1124]], [[temastj 290]], [[adi 5135]], [[adc 49]], [[adpf 357]]. Siglas fixas: {{ implode(', ', array_keys(config('concursos.leis.catalogo'))) }}.</p>
