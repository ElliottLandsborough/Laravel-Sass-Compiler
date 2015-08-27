{{-- Open the form --}}
{!! Form::open() !!}

{{-- Check that any import lines were detected --}}
@if (isset($imports) && $imports && count($imports))
	<ul class="file-to-import">
	{{-- Loop through the import files --}}
	@foreach ($imports as $id => $import)
		<li>
			{{-- Checkbox, check it if it was checked before --}}
			{!! Form::checkbox('import_file['.$id.']', $import, isset($request['import_file'][$id]), array('id'=>'import_file_'.$id)) !!}
			{{-- Generate the checkbox and its label --}}
			{!! Form::label('import_file_'.$id, $import) !!}
		</li>
	@endforeach
	</li>
@endif

{{-- example input --}}
{!! Form::label('input_name', 'Input Title') !!}
{!! Form::text('input_name', 'body { color: #000;}') !!}

{{-- submit button --}}
{!! Form::submit('Compile') !!}

{{-- close the form --}}
{!! Form::close() !!}